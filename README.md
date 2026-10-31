# Weather Report Solutions — Enterprise Cloud Security Architecture

[![AWS Architecture](https://img.shields.io/badge/AWS-VPC%20%7C%20EC2%20%7C%20S3%20%7C%20Budgets-232F3E?logo=amazon-aws&logoColor=white)](https://aws.amazon.com/)
[![Linux OS](https://img.shields.io/badge/OS-Ubuntu%2024.04%20LTS-E95420?logo=ubuntu&logoColor=white)](https://ubuntu.com/)
[![SIEM Platform](https://img.shields.io/badge/SIEM-Wazuh%204.9.2-00599C?logo=wazuh&logoColor=white)](https://wazuh.com/)
[![NIDS Engine](https://img.shields.io/badge/NIDS-Suricata%208.0.7-EF3B24)](https://suricata.io/)
[![Host Hardening](https://img.shields.io/badge/Hardening-CIS%20Level%201%20%7C%20Fail2ban%20%7C%20UFW-success)](https://www.cisecurity.org/)
[![DevSecOps CI/CD](https://img.shields.io/badge/DevSecOps-Gitleaks%20%7C%20Semgrep%20%7C%20Trivy%20%7C%20Checkov-blueviolet?logo=github-actions&logoColor=white)](https://github.com/features/actions)

---

## 1. Resumen Ejecutivo (Executive Summary)

### Castellano
**Weather Report Solutions** es un proyecto personal de ingenieria cloud y ciberseguridad defensiva disenado para adquirir un dominio practico y exhaustivo en arquitectura cloud en **Amazon Web Services (AWS)**, administracion de sistemas Linux, bastionado de servidores, monitorizacion centralizada y pipelines DevSecOps.
El proyecto simula el entorno de produccion de una plataforma corporativa SaaS de telemetria climatica. Desplegado en la region `eu-west-1` (Irlanda) bajo estrictos criterios de optimizacion de costes (**AWS Free Tier**), implementa una red VPC segmentada en 4 capas (Publica, Aplicacion, Datos y Seguridad), proteccion perimetral con **Cloudflare WAF / Proxy**, bastionado de sistemas bajo **CIS Benchmark Nivel 1**, monitorizacion e integracion en tiempo real del SIEM **Wazuh 4.9.2** con el motor NIDS **Suricata 8.0.7**, y un pipeline automatizado de **DevSecOps en GitHub Actions**.

### English
**Weather Report Solutions** is an independent, hands-on cloud engineering and defensive cybersecurity project developed to master real-world **Amazon Web Services (AWS)** architecture, Linux system administration, host hardening, security operations, and DevSecOps practices.
The lab simulates an enterprise-grade production environment for an industrial weather telemetry SaaS platform. Deployed in AWS region `eu-west-1` adhering to strict Free Tier cost-engineering parameters, the infrastructure enforces a four-tier network segmentation (Public, Application, Data, and Security VPC subnets). It integrates perimeter defenses via **Cloudflare WAF**, operating system hardening compliant with **CIS Benchmark Level 1**, centralized intrusion detection combining **Wazuh 4.9.2 SIEM** and **Suricata 8.0.7 NIDS**, and automated security testing powered by **GitHub Actions** (Gitleaks, Semgrep, Trivy, and Checkov).

---

## 2. Indice de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo-executive-summary)
2. [Diagrama de Arquitectura Multicapa](#3-diagrama-de-arquitectura-multicapa)
3. [Topologia de Red y Matriz de Minimo Privilegio](#4-topologia-de-red-y-matriz-de-minimo-privilegio)
4. [Despliegue Tecnico y Galeria de Evidencias Graficas](#5-despliegue-tecnico-y-galeria-de-evidencias-graficas)
   - [Fase 00: Gobernanza de Cuenta, IAM y Presupuesto](#51-fase-00-gobernanza-de-cuenta-iam-y-presupuesto)
   - [Fase 01 y 02: Infraestructura VPC, Subredes y Security Groups](#52-fase-01-y-02-infraestructura-vpc-subredes-y-security-groups)
   - [Fase 03 y 04: Capa Web Nginx, Servidor de Aplicacion y Base de Datos](#53-fase-03-y-04-capa-web-nginx-servidor-de-aplicacion-y-base-de-datos)
   - [Fase 05: Hardening Linux CIS Nivel 1, UFW, Fail2ban y Kernel](#54-fase-05-hardening-linux-cis-nivel-1-ufw-fail2ban-y-kernel)
   - [Fase 06: Despliegue de SIEM Wazuh 4.9.2 y NIDS Suricata 8.0.7](#55-fase-06-despliegue-de-siem-wazuh-492-y-nids-suricata-807)
   - [Fase 07: Pipeline DevSecOps en GitHub Actions](#56-fase-07-pipeline-devsecops-en-github-actions)
5. [Galeria Complementaria de Evidencias de Laboratorio](#6-galeria-complementaria-de-evidencias-de-laboratorio)
6. [Ingenieria de Costes y Resolucion de Incidencias Complejas](#7-ingenieria-de-costes-y-resolucion-de-incidencias-complejas)
7. [Estructura del Repositorio](#8-estructura-del-repositorio)
8. [Autor](#9-autor)

---

## 3. Diagrama de Arquitectura Multicapa

```
                                  [ INTERNET ]
                                        |
                           ( HTTPS / TLS 1.3 Inspection )
                                        v
                          +---------------------------+
                          |   Cloudflare WAF / DNS    |
                          |  Anti-DDoS / SSL Offload  |
                          +---------------------------+
                                        |
                 [ Public IPv4 / Elastic Dynamic Forwarding ]
                                        v
+===================================================================================+
| AWS VPC: wr-lab-vpc (10.0.0.0/16) - eu-west-1 (Ireland)                           |
|                                                                                   |
|  +-----------------------------------------------------------------------------+  |
|  | Subred Publica (10.0.1.0/24)                                                |  |
|  |                                                                             |  |
|  |   +-----------------------------------------------------------------------+ |  |
|  |   | wr-lab-nginx (10.0.1.45) - Ubuntu 24.04 LTS (t2.micro)                | |  |
|  |   | - Nginx Reverse Proxy (SSL Termination / HTTP Upstream)                | |  |
|  |   | - Bastion Host / SSH Jumpbox (Ed25519 ProxyJump Zero Trust)           | |  |
|  |   | - NAT Gateway Gateway Route for Private Subnets                       | |  |
|  |   | - Suricata 8.0.7 NIDS (ens5 Promiscuous Sniffing / eve.json)           | |  |
|  |   | - Wazuh Agent 4.9.2 (Agent ID: 001)                                   | |  |
|  |   +-----------------------------------------------------------------------+ |  |
|  +-----------------------------------------------------------------------------+  |
|         | Proxy Pass (Port 80)           | SSH Jump / Admin           | NAT Flow  |
|         v                                v                            v           |
|  +--------------------------------+   +----------------------------------------+  |
|  | Subred Aplicacion (10.0.11.0/24)|   | Subred Seguridad (10.0.31.0/24)        |  |
|  |                                |   |                                        |  |
|  |  wr-lab-app (10.0.11.10)        |   |  wr-lab-wazuh (10.0.31.10)             |  |
|  |  - PHP 8.3 FPM Application     |   |  - Wazuh Manager 4.9.2                 |  |
|  |  - Lynis Hardened Kernel       |   |  - OpenSearch Indexer (Port 9200)      |  |
|  |  - UFW / Fail2ban               |   |  - Wazuh Dashboard (Port 443 -> 8443)  |  |
|  |  - Wazuh Agent 4.9.2           |   |  - 4 GiB SWAP + JVM Memory Fix         |  |
|  +--------------------------------+   +----------------------------------------+  |
|         |                                      ^                                  |
|         | SQL (5432) / Redis (6379)            | Telemetry Events (Port 1514)     |
|         v                                      |                                  |
|  +--------------------------------+            |                                  |
|  | Subred Datos (10.0.21.0/24)    |            |                                  |
|  |                                |            |                                  |
|  |  wr-lab-data (10.0.21.10)       |------------+                                  |
|  |  - PostgreSQL 16 (Strict Bind) |                                               |
|  |  - Redis Cache (Password Auth) |                                               |
|  |  - UFW Network Isolation       |                                               |
|  |  - Wazuh Agent 4.9.2           |                                               |
|  +--------------------------------+                                               |
+===================================================================================+
```

---

## 4. Topologia de Red y Matriz de Minimo Privilegio

### 4.1. Segmentacion de Subredes (VPC `10.0.0.0/16`)

| Subred | Bloque CIDR | Zona (AZ) | Enrutamiento Principal | Rol Operativo |
|---|---|---|---|---|
| `wr-pub-sub-a` | `10.0.1.0/24` | `eu-west-1a` | Internet Gateway (`igw`) | Proxy inverso publico, Bastion SSH y pasarela NAT. |
| `wr-pub-sub-b` | `10.0.2.0/24` | `eu-west-1b` | Internet Gateway (`igw`) | Redundancia geografica perimetral. |
| `wr-app-sub-a` | `10.0.11.0/24` | `eu-west-1a` | Salida a traves de NAT en `wr-nginx` | Servidor privado de aplicacion PHP 8.3 FPM. |
| `wr-data-sub-a` | `10.0.21.0/24` | `eu-west-1a` | Totalmente aislada (Solo comunicacion VPC) | Base de datos PostgreSQL 16 y cache Redis. |
| `wr-sec-sub-a` | `10.0.31.0/24` | `eu-west-1a` | Salida a traves de NAT en `wr-nginx` | Servidor SIEM Wazuh All-in-One. |

### 4.2. Matriz de Security Groups (Minimo Privilegio)

| Security Group | Reglas de Entrada (Ingress) | Reglas de Salida (Egress) | Finalidad de Seguridad |
|---|---|---|---|
| `wr-sg-nginx` | HTTP (80) & HTTPS (443) publicos; SSH (22) desde IP autorizada. | Todo el trafico hacia la VPC y hacia Internet para enrutamiento NAT. | Barrera exterior de contencion perimetral. |
| `wr-sg-app` | HTTP (80) exclusivo desde `wr-sg-nginx`; SSH (22) exclusivo desde `wr-sg-nginx`. | Salida a PostgreSQL (5432) y Redis (6379); eventos Wazuh (1514). | Aislamiento completo frente a accesos directos desde Internet. |
| `wr-sg-data` | PostgreSQL (5432) y Redis (6379) exclusivo desde `wr-sg-app`; SSH (22) desde `wr-sg-nginx`. | Eventos de auditoria Wazuh (1514) a `wr-sg-wazuh`. | Maxima restriccion de acceso a datos sensibles. |
| `wr-sg-wazuh` | Agentes Wazuh (1514/1515) y API (55000) desde la VPC (`10.0.0.0/16`); HTTPS (443) desde `wr-sg-nginx`. | Peticiones HTTP/HTTPS via NAT para descarga de firmas NIDS. | Servidor de correlacion y monitorizacion continua. |

---

## 5. Despliegue Tecnico y Galeria de Evidencias Graficas

### 5.1. Fase 00: Gobernanza de Cuenta, IAM y Presupuesto
* Configuracion de cuenta administrativa delegada (`wr-admin`) para evitar el uso de la cuenta Root.
* Habilitacion de Autenticacion Multifactor (MFA) obligatoria mediante aplicacion TOTP.
* Creacion de alertas presupuestarias automatizadas en **AWS Budgets** con limite en $1.00 USD para prevenir cualquier coste fuera del Free Tier.

<p align="center">
  <img src="docs/evidence/phase00_governance/Pasted%20image%2020260916121445.png" width="48%" alt="Activacion de MFA en IAM" />
  <img src="docs/evidence/phase00_governance/Pasted%20image%2020260916121549.png" width="48%" alt="Alerta en AWS Budgets a 1.00 USD" />
</p>
<p align="center"><em>Figura 1: Activacion de dispositivo MFA en cuenta AWS y creacion de presupuesto de coste cero con alertas tempranas.</em></p>

---

### 5.2. Fase 01 y 02: Infraestructura VPC, Subredes y Security Groups
* Diseno de la VPC `wr-lab-vpc` con direccionamiento `10.0.0.0/16` en `eu-west-1`.
* Segmentacion en subredes publicas y privadas con distribucion multi-zona.
* Enrutamiento mediante tablas diferenciadas para trafico hacia Internet Gateway y trafico privado interno.
* Grupos de Seguridad implementando politicas de denegacion por defecto (*Default Deny*).

<p align="center">
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20161728.png" width="48%" alt="Mapa de Recursos de la VPC" />
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20161908.png" width="48%" alt="Detalle de Subredes Creadas" />
</p>
<p align="center"><em>Figura 2: Mapa de recursos de la VPC wr-lab-vpc y listado de subredes publicas y privadas.</em></p>

<p align="center">
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20164041.png" width="48%" alt="Tablas de Enrutamiento" />
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20164128.png" width="48%" alt="Asociaciones de Subredes" />
</p>
<p align="center"><em>Figura 3: Enrutamiento perimetral con salida al Internet Gateway wr-lab-igw.</em></p>

<p align="center">
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20164200x.png" width="31%" alt="Internet Gateway wr-lab-igw" />
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20164424.png" width="31%" alt="Listado de Security Groups" />
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20164529.png" width="31%" alt="Reglas SG wr-sg-nginx" />
</p>
<p align="center">
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20164627.png" width="31%" alt="Reglas SG wr-sg-app" />
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20164822.png" width="31%" alt="Reglas SG wr-sg-data" />
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20165200.png" width="31%" alt="Reglas SG wr-sg-wazuh" />
</p>
<p align="center"><em>Figura 4: Inventario completo de Security Groups reflejando el principio de menor privilegio entre capas.</em></p>

---

### 5.3. Fase 03 y 04: Capa Web Nginx, Servidor de Aplicacion y Base de Datos
* Lanzamiento de las instancias EC2: `wr-lab-nginx` (publica), `wr-lab-app` (privada) y `wr-lab-data` (privada).
* Configuracion de Nginx como Reverse Proxy con terminacion SSL y upstream HTTP hacia `10.0.11.10`.
* Bastion Host configurado en `wr-nginx` para administracion Zero Trust mediante OpenSSH `ProxyJump` con claves Ed25519.
* Despliegue de **PostgreSQL 16** con escucha vinculada unicamente a la direccion privada `10.0.21.10` y autenticacion por contrasena robusta.
* Despliegue de **Redis** para cache de sesiones y consultas de alta concurrencia.
* Inicializacion del esquema relacional y despliegue de la aplicacion SaaS en PHP 8.3 FPM.

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20132430.png" width="48%" alt="Consola EC2: Instancias en ejecucion" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20132440.png" width="48%" alt="Detalles de IPs privadas y subredes" />
</p>
<p align="center"><em>Figura 5: Instancias EC2 desplegadas en sus respectivas subredes con direccionamiento privado estricto.</em></p>

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20133301.png" width="48%" alt="Acceso SSH y verificacion de red" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20133503.png" width="48%" alt="Conexion ProxyJump hacia wr-lab-app" />
</p>
<p align="center"><em>Figura 6: Conexion administrativa remota utilizando SSH ProxyJump a traves del nodo Bastion.</em></p>

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20134943.png" width="48%" alt="Verificacion de Nginx Reverse Proxy" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20140449.png" width="48%" alt="Servicio PHP 8.3 FPM activo" />
</p>
<p align="center"><em>Figura 7: Verificacion de respuesta HTTP de Nginx y ejecucion del motor de aplicacion PHP 8.3 FPM.</em></p>

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20135510.png" width="48%" alt="Instalacion y arranque de PostgreSQL 16" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20135758.png" width="48%" alt="Servicio Redis activo y test PING-PONG" />
</p>
<p align="center"><em>Figura 8: Capa de datos en wr-lab-data: PostgreSQL 16 y Redis respondiendo internamente.</em></p>

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20142057.png" width="48%" alt="Aplicacion Weather Report operativa" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20142839.png" width="48%" alt="Dashboard web con telemetria meteorologica" />
</p>
<p align="center"><em>Figura 9: Plataforma web corporativa Weather Report Solutions funcionando a traves del proxy.</em></p>

---

### 5.4. Fase 05: Hardening Linux CIS Nivel 1, UFW, Fail2ban y Kernel
* Configuracion del firewall local **UFW** en todos los servidores, bloqueando todo trafico entrante no autorizado.
* Implementacion de **Fail2ban** para baneo automatico progresivo de IPs frente a ataques de fuerza bruta en SSH y sondeos web.
* Hardening del kernel Linux mediante parametros en `/etc/sysctl.d/60-security-hardening.conf` (Reverse Path Filtering contra IP Spoofing, proteccion TCP SYN Cookies e ignorado de broadcast ICMP).
* Auditoria de cumplimiento de seguridad con la herramienta **Lynis** superando el umbral de referencia.
* Proteccion de OpenSSH: Desactivacion de autenticacion por contrasena, forzado de llaves Ed25519 y establecimiento de banners disuasorios.

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20143650.png" width="48%" alt="Configuracion UFW Firewall" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20143910.png" width="48%" alt="Estado de reglas activas de UFW" />
</p>
<p align="center"><em>Figura 10: Activacion y verificacion de politicas de cortafuegos UFW a nivel de host.</em></p>

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20144738.png" width="48%" alt="Instalacion y configuracion de Fail2ban" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20144852.png" width="48%" alt="Estado de las carceles SSH y Nginx en Fail2ban" />
</p>
<p align="center"><em>Figura 11: Carceles de Fail2ban monitorizando activamente intentos anomalos de inicio de sesion.</em></p>

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20153401.png" width="48%" alt="Auditoria de seguridad CIS con Lynis" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20153650.png" width="48%" alt="Puntuacion y reporte final de Lynis" />
</p>
<p align="center"><em>Figura 12: Auditoria automatizada Lynis certificando el bastionado del sistema operativo.</em></p>

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20154749.png" width="48%" alt="Hardening de configuracion SSH" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20160600.png" width="48%" alt="Parametros de seguridad de Kernel sysctl" />
</p>
<p align="center"><em>Figura 13: Aplicacion de parametros de kernel reforzados y cierre de metodos de acceso SSH inseguros.</em></p>

---

### 5.5. Fase 06: Despliegue de SIEM Wazuh 4.9.2 y NIDS Suricata 8.0.7
* Despliegue de Wazuh All-in-One en la instancia `wr-lab-wazuh` (`10.0.31.10`) sobre Ubuntu 24.04 LTS.
* **Optimizacion de Memoria:** Resolucion del error de k-NN en OpenSearch Indexer mediante drop-in Systemd, forzando Heap a 512 MB y habilitando un archivo de intercambio SWAP de 4 GiB, permitiendo la ejecucion estable de Wazuh en una instancia `t3.micro` sin coste adicional.
* Instalacion del motor NIDS **Suricata 8.0.7** en `wr-lab-nginx` en modo promiscuo sobre la interfaz `ens5`.
* Compilacion e integracion de **69,176 reglas de Emerging Threats (ET)** para deteccion de intrusiones de red.
* Ingestion del archivo `eve.json` de Suricata en tiempo real mediante `ossec.conf` de Wazuh Agent.
* Acceso seguro al dashboard web mediante tunel SSH Zero Trust (`localhost:8443`).

<p align="center">
  <img src="docs/evidence/phase06_wazuh_indexer_deployment/Screenshot%202026-10-19%20211148.png" width="48%" alt="Instalacion de paquetes Wazuh All-in-One" />
  <img src="docs/evidence/phase06_wazuh_indexer_deployment/Screenshot%202026-10-19%20214045.png" width="48%" alt="Drop-in Systemd para arranque de OpenSearch" />
</p>
<p align="center"><em>Figura 14: Proceso de instalacion de Wazuh y parcheo de la memoria JVM en Systemd.</em></p>

<p align="center">
  <img src="docs/evidence/phase06_wazuh_indexer_deployment/Screenshot%202026-10-19%20214648.png" width="48%" alt="Wazuh Indexer activo en puerto 9200" />
  <img src="docs/evidence/phase06_wazuh_indexer_deployment/Screenshot%202026-10-19%20214857.png" width="48%" alt="Wazuh Manager y API activos" />
</p>
<p align="center"><em>Figura 15: Verificacion de servicios clave del SIEM: Indexer, Manager y Filebeat activos.</em></p>

<p align="center">
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20174619.png" width="48%" alt="Pantalla de inicio de sesion de Wazuh Dashboard" />
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20180211.png" width="48%" alt="Cuadro de mando principal del SIEM" />
</p>
<p align="center"><em>Figura 16: Acceso seguro al Dashboard de Wazuh a traves del tunel SSH local cifrado.</em></p>

<p align="center">
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20184306.png" width="48%" alt="Compilacion de 69.176 reglas en Suricata" />
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20185042.png" width="48%" alt="Suricata NIDS monitorizando ens5" />
</p>
<p align="center"><em>Figura 17: Motor NIDS Suricata compilando reglas Emerging Threats y capturando paquetes en tiempo real.</em></p>

<p align="center">
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20200005.png" width="48%" alt="Estado de agentes Wazuh activos" />
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20202037.png" width="48%" alt="Telemetria de agentes y eventos recibidos" />
</p>
<p align="center"><em>Figura 18: Agentes de Wazuh reportando telemetria y eventos desde los servidores de la VPC.</em></p>

<p align="center">
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20204009.png" width="48%" alt="Matriz MITRE ATT&CK en Wazuh" />
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20210046.png" width="48%" alt="Alertas de seguridad de Suricata en Dashboard" />
</p>
<p align="center"><em>Figura 19: Correlacion de eventos de red y sistema mapeados contra el framework MITRE ATT&CK.</em></p>

<p align="center">
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20212046.png" width="48%" alt="Deteccion en vivo de inyeccion SQL" />
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20212130.png" width="48%" alt="Deteccion de ataque de fuerza bruta SSH" />
</p>
<p align="center"><em>Figura 20: Deteccion y clasificacion en tiempo real de ataques simulados en el laboratorio defensivo.</em></p>

---

### 5.6. Fase 07: Pipeline DevSecOps en GitHub Actions
* Implementacion de un flujo continuo de seguridad en `.github/workflows/devsecops.yml`.
* **Gitleaks:** Deteccion automatica de secretos, certificados y tokens expuestos.
* **Semgrep:** Analisis estatico de seguridad en codigo (SAST) buscando vulnerabilidades OWASP.
* **Trivy:** Escaneo del sistema de archivos y dependencias de paquetes en busca de CVEs publicos.
* **Checkov:** Auditoria estatica de cumplimiento y seguridad sobre la infraestructura como codigo.

<p align="center">
  <img src="docs/evidence/phase07_devsecops_pipeline/Screenshot%202026-10-22%20171302.png" width="48%" alt="Ejecucion de jobs paralelos en GitHub Actions" />
  <img src="docs/evidence/phase07_devsecops_pipeline/Screenshot%202026-10-22%20171514.png" width="48%" alt="Pipeline DevSecOps completado exitosamente" />
</p>
<p align="center"><em>Figura 21: Pipeline de DevSecOps ejecutando escaneos concurrentes con exito en cada commit.</em></p>

---

## 6. Galeria Complementaria de Evidencias de Laboratorio

<details>
<summary><strong>Desplegar galeria adicional de capturas tecnicas de configuracion (Haz clic para ver mas)</strong></summary>

<br />

#### Configuracion de Sistema y Pruebas de Red
<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20133425.png" width="31%" alt="Informacion de Release Ubuntu 24.04" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20133433.png" width="31%" alt="Configuracion de red ens5" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20134425.png" width="31%" alt="Pruebas de conectividad curl" />
</p>

#### Inicializacion de Base de Datos y Cache
<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20135657.png" width="31%" alt="Verificacion DSN de conexion a PostgreSQL" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20135907.png" width="31%" alt="Importacion de db_schema.sql" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20140623.png" width="31%" alt="Nginx FastCGI pass config" />
</p>

#### Auditoria de Procesos y Hardening Detallado
<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20153643.png" width="31%" alt="Sugerencias de bastionado en Lynis" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20154728.png" width="31%" alt="Generacion de par de llaves Ed25519" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20160533.png" width="31%" alt="Verificacion de sysctl.conf activo" />
</p>

#### Monitorizacion y Alertas de Telemetria en Wazuh
<p align="center">
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20201602.png" width="31%" alt="Integracion eve.json en ossec.conf" />
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20204918.png" width="31%" alt="Alertas de FIM integradas" />
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20211819.png" width="31%" alt="Logs estructurados en Dashboard" />
</p>

</details>

---

## 7. Ingenieria de Costes y Resolucion de Incidencias Complejas

### 7.1. k-NN Native Memory Underflow en OpenSearch (Wazuh Indexer)
* **Diagnostico:** En instancias con 1 GiB de RAM fisica (`t3.micro`), el plugin k-NN de OpenSearch calcula el limite de memoria no-heap como:
  $$\text{Memoria Nativa} = \text{RAM Fisica (920 MiB)} - \text{Heap JVM (1024 MiB)} = -59.107.328\text{ bytes}$$
  Esto disparaba la excepcion `IllegalArgumentException: Values less than -1 bytes are not supported: -59107328b` provocando la caida del indexador durante el arranque.
* **Solucion de Ingenieria:** Se creo una unidad de override en Systemd (`/etc/systemd/system/wazuh-indexer.service.d/override.conf`) con `TimeoutStartSec=600` y un comando `ExecStartPre` con privilegios elevados que inyecta `-Xms512m -Xmx512m` en `/etc/wazuh-indexer/jvm.options`. Esto garantizo un calculo de memoria nativa positivo ($+428\text{ MB}$) y un arranque estable utilizando un archivo de intercambio SWAP de 4 GiB sin necesidad de actualizar a instancias de pago.

### 7.2. Socket Activation en Ubuntu 24.04 OpenSSH
* **Problema:** Ubuntu 24.04 LTS activa por defecto `ssh.socket`, cerrando conexiones concurrentes y provocando caidas durante accesos rapidos via ProxyJump a traves del nodo Bastion.
* **Solucion:** Migracion a servicio continuo mediante `systemctl disable --now ssh.socket && systemctl enable --now ssh.service`, combinada con multiplexacion en el cliente (`ControlMaster auto`, `ControlPersist 10m` en `~/.ssh/config`).

### 7.3. Compatibilidad Estricta de Versiones Wazuh Manager vs. Agent
* **Problema:** Wazuh Manager 4.9.2 rechaza estrictamente agentes con versiones superiores (`Agent version must be lower or equal to manager version`). El repositorio estandar instalaba la version `4.14`.
* **Solucion:** Pinning explicito a `wazuh-agent=4.9.2-1` mediante APT y eliminacion de etiquetas no soportadas en `ossec.conf` (`users`, `groups`, `services`).

---

## 8. Estructura del Repositorio

```
.
├── .github/
│   └── workflows/
│       └── devsecops.yml        # Pipeline CI/CD con Gitleaks, Semgrep, Trivy y Checkov
├── app/                         # Codigo fuente de la aplicacion web corporativa SaaS
│   ├── admin/panel.php          # Panel administrativo interno
│   ├── api/v1/                  # Endpoints REST de telemetria climatica
│   ├── config.php               # Configuracion de conexion a PostgreSQL y Redis
│   ├── dashboard.php            # Cuadro de mando web para clientes
│   └── db_schema.sql            # Esquema DDL de la base de datos
├── docs/                        # Documentacion y evidencias del proyecto
│   └── evidence/                # 96 capturas de pantalla organizadas por fases
│       ├── phase00_governance/
│       ├── phase01_vpc_and_security_groups/
│       ├── phase03_04_servers_and_hardening/
│       ├── phase06_wazuh_indexer_deployment/
│       ├── phase06_wazuh_suricata_dashboard/
│       └── phase07_devsecops_pipeline/
├── .gitignore                   # Exclusiones de seguridad (credenciales, claves .pem, apuntes)
└── README.md                    # Documentacion tecnica central del proyecto
```

---

## 9. Autor

* **Alejandro De Luque**
* Proyecto personal de especializacion practica en **Arquitectura Cloud en AWS**, **Sistemas Linux**, **SIEM / SOC Operations** y **DevSecOps**.
