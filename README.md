# Weather Report Solutions — Enterprise Cloud Security Architecture

[![AWS](https://img.shields.io/badge/AWS-VPC%20%7C%20EC2%20%7C%20S3-232F3E?logo=amazon-aws&logoColor=white)](https://aws.amazon.com/)
[![OS](https://img.shields.io/badge/OS-Ubuntu%2024.04%20LTS-E95420?logo=ubuntu&logoColor=white)](https://ubuntu.com/)
[![SIEM](https://img.shields.io/badge/SIEM-Wazuh%204.9.2-00599C?logo=wazuh&logoColor=white)](https://wazuh.com/)
[![NIDS](https://img.shields.io/badge/NIDS-Suricata%208.0.7-EF3B24)](https://suricata.io/)
[![Hardening](https://img.shields.io/badge/Hardening-CIS%20Level%201%20%7C%20Fail2ban%20%7C%20UFW-success)](https://www.cisecurity.org/)
[![DevSecOps](https://img.shields.io/badge/DevSecOps-Gitleaks%20%7C%20Semgrep%20%7C%20Trivy%20%7C%20Checkov-blueviolet?logo=github-actions&logoColor=white)](https://github.com/features/actions)

---

## 1. Resumen Ejecutivo (Executive Summary)

### Castellano
Este proyecto documenta el diseno, despliegue, securizacion y monitorizacion continua de una infraestructura cloud corporativa para la empresa ficticia **Weather Report Solutions S.L.** (marca de KX Digital), una plataforma SaaS que comercializa datos meteorologicos y microclimaticos.
Alojado en **Amazon Web Services (AWS)** bajo la region `eu-west-1` (Irlanda) y optimizado para la capa gratuita (**Free Tier**), el entorno cuenta con una red VPC segmentada en cuatro capas logicas (Publica, Aplicacion, Datos y Seguridad), proteccion perimetral mediante **Cloudflare WAF / Proxy**, bastionado de sistemas operativos bajo el estandar **CIS Benchmark Nivel 1**, monitorizacion centralizada con el SIEM **Wazuh 4.9.2**, inspeccion de trafico de red con el NIDS **Suricata 8.0.7** y un pipeline automatizado de **DevSecOps en GitHub Actions** con escaneo de secretos, SAST y auditoria de dependencias e infraestructura como codigo.

### English
This repository presents the end-to-end design, implementation, system hardening, and continuous monitoring of an enterprise cloud infrastructure supporting **Weather Report Solutions S.L.**, an industrial weather telemetry SaaS platform.
Deployed in **Amazon Web Services (AWS)** region `eu-west-1` under strict Free Tier cost engineering principles, the architecture enforces a four-tier network segmentation (Public, Application, Data, and Security VPC subnets). Perimeter security is reinforced through **Cloudflare WAF / Reverse Proxy**, host hardening aligns with **CIS Benchmark Level 1**, real-time intrusion detection is powered by **Suricata 8.0.7 NIDS**, centralized event correlation runs on **Wazuh SIEM 4.9.2**, and code security is continuously enforced via an automated **DevSecOps GitHub Actions CI/CD pipeline** (Gitleaks, Semgrep, Trivy, and Checkov).

---

## 2. Diagrama de Arquitectura Cloud Multicapa

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

## 3. Matriz de Ingenieria de Red y Seguridad

### 3.1. Segmentacion de Subredes (VPC `10.0.0.0/16`)

| Subred | Bloque CIDR | Zona (AZ) | Enrutamiento | Proposito Operativo |
|---|---|---|---|---|
| `wr-pub-sub-a` | `10.0.1.0/24` | `eu-west-1a` | Internet Gateway (`igw`) | Proxy inverso publico, Bastion SSH y NAT Gateway. |
| `wr-pub-sub-b` | `10.0.2.0/24` | `eu-west-1b` | Internet Gateway (`igw`) | Alta disponibilidad y redundancia de entrada. |
| `wr-app-sub-a` | `10.0.11.0/24` | `eu-west-1a` | Salida a traves de NAT en `wr-nginx` | Servidor privado de aplicacion PHP 8.3. |
| `wr-data-sub-a` | `10.0.21.0/24` | `eu-west-1a` | Aislada (Solo comunicacion con App y Bastion) | Base de datos PostgreSQL 16 y cache Redis. |
| `wr-sec-sub-a` | `10.0.31.0/24` | `eu-west-1a` | Salida a traves de NAT en `wr-nginx` | Servidor de seguridad Wazuh SIEM All-in-One. |

### 3.2. Matriz de Security Groups (Minimo Privilegio)

| Security Group | Reglas de Entrada (Ingress) | Reglas de Salida (Egress) | Justificacion |
|---|---|---|---|
| `wr-sg-nginx` | HTTP (80) & HTTPS (443) desde Cloudflare / Internet; SSH (22) restringido a IP admin. | Todo el trafico hacia la VPC y hacia Internet (para NAT). | Actua como frontera perimetral controlada. |
| `wr-sg-app` | HTTP (80) exclusivo desde `wr-sg-nginx`; SSH (22) exclusivo desde `wr-sg-nginx`. | Salida a PostgreSQL (5432) y Redis (6379) hacia `wr-sg-data`; eventos Wazuh (1514) a `wr-sg-wazuh`. | Totalmente invisible desde Internet publico. |
| `wr-sg-data` | PostgreSQL (5432) y Redis (6379) exclusivo desde `wr-sg-app`; SSH (22) desde `wr-sg-nginx`. | Eventos Wazuh (1514) a `wr-sg-wazuh`. | Sin salida directa a Internet. |
| `wr-sg-wazuh` | Agente Wazuh (1514/1515) y API (55000) desde la VPC (`10.0.0.0/16`); HTTPS (443) desde `wr-sg-nginx`. | Salida a repositorios via NAT para firmas ET y actualizaciones. | Centro de correlacion de telemetria defensiva. |

---

## 4. Evidencias Graficas de Despliegue por Fases

### Fase 00: Gobernanza de Cuenta, IAM y Presupuesto
Configuracion de cuenta administrativa delegada, activacion de MFA por hardware/TOTP en cuenta Root y establecimiento de alertas tempranas en **AWS Budgets** con un umbral de $1.00 USD.

<p align="center">
  <img src="docs/evidence/phase00_governance/Pasted%20image%2020260916121445.png" width="45%" alt="AWS Governance MFA" />
  <img src="docs/evidence/phase00_governance/Pasted%20image%2020260916121549.png" width="45%" alt="AWS Budgets Setup" />
</p>

---

### Fase 01 y 02: Infraestructura VPC, Subnetting y Security Groups
Despliegue de la VPC `10.0.0.0/16` en `eu-west-1`, asignacion de tablas de enrutamiento y definicion de Security Groups con aislamiento entre capas.

<p align="center">
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20161728.png" width="48%" alt="VPC Resource Map" />
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20161908.png" width="48%" alt="Subnet Segmentation" />
</p>
<p align="center">
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20164041.png" width="48%" alt="Route Table Configuration" />
  <img src="docs/evidence/phase01_vpc_and_security_groups/Screenshot%202026-10-08%20164128.png" width="48%" alt="Security Group Ingress Rules" />
</p>

---

### Fase 03, 04 y 05: Servidores, Proxy Inverso y Hardening Linux
Lanzamiento de las instancias EC2 (`wr-lab-nginx`, `wr-lab-app`, `wr-lab-data`), configuracion del proxy inverso Nginx con reenrutamiento hacia la IP privada de aplicacion (`10.0.11.10`), instalacion de PostgreSQL 16 y Redis con bind interno, y aplicacion de **CIS Benchmark Nivel 1** (UFW, Fail2ban y SSH multiplexado).

<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20132430.png" width="48%" alt="EC2 Instances Running" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20133301.png" width="48%" alt="PostgreSQL & Redis Status" />
</p>
<p align="center">
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20134943.png" width="48%" alt="Nginx Reverse Proxy Test" />
  <img src="docs/evidence/phase03_04_servers_and_hardening/Screenshot%202026-10-15%20140737.png" width="48%" alt="UFW & Fail2ban Jails Active" />
</p>

---

### Fase 06: SIEM Wazuh 4.9.2 y NIDS Suricata 8.0.7
Despliegue de Wazuh All-in-One en `wr-lab-wazuh` con optimizacion de memoria en instancia `t3.micro`. Resolucion de errores del circuit breaker de OpenSearch k-NN mediante drop-in Systemd (`override.conf`) forzando Heap a 512 MB. Instalacion de Suricata en `wr-lab-nginx` con 69,176 reglas de Emerging Threats y reenvio de alertas JSON a Wazuh Manager. Acceso seguro al dashboard mediante tunel SSH Zero Trust (`localhost:8443`).

<p align="center">
  <img src="docs/evidence/phase06_wazuh_indexer_deployment/Screenshot%202026-10-19%20211148.png" width="48%" alt="Wazuh Indexer Startup" />
  <img src="docs/evidence/phase06_wazuh_indexer_deployment/Screenshot%202026-10-19%20214045.png" width="48%" alt="Wazuh Systemd Service" />
</p>
<p align="center">
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20180211.png" width="48%" alt="Wazuh Dashboard Overview" />
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20181219.png" width="48%" alt="Suricata NIDS Event Stream" />
</p>
<p align="center">
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20200005.png" width="48%" alt="Wazuh Agents Active Overview" />
  <img src="docs/evidence/phase06_wazuh_suricata_dashboard/Screenshot%202026-10-20%20210046.png" width="48%" alt="Real Time Security Alerts" />
</p>

---

### Fase 07: Pipeline DevSecOps en GitHub Actions
Configuracion del flujo automatizado de seguridad en `.github/workflows/devsecops.yml`. Escaneo paralelo en cada commit y pull request combinando:
1. **Gitleaks:** Deteccion automatizada de secretos, claves privadas y API keys hardcodeadas.
2. **Semgrep:** Analisis estatico de codigo seguro (SAST) enfocado en prevenir inyecciones SQL y fallos de autorizacion.
3. **Trivy:** Auditoria de vulnerabilidades conocidas (CVE) en dependencias y sistema de archivos.
4. **Checkov:** Escaneo estatico de seguridad y cumplimiento en ficheros de infraestructura como codigo (IaC).

<p align="center">
  <img src="docs/evidence/phase07_devsecops_pipeline/Screenshot%202026-10-22%20171302.png" width="48%" alt="GitHub Actions Security Jobs" />
  <img src="docs/evidence/phase07_devsecops_pipeline/Screenshot%202026-10-22%20171514.png" width="48%" alt="DevSecOps Clean Pipeline Passing" />
</p>

---

## 5. Optimizacion de Ingenieria de Costes y Resolucion de Incidencias

### 5.1. k-NN Native Memory Underflow en OpenSearch (Wazuh Indexer)
* **Diagnostico:** En instancias con 1 GiB de RAM fisica, el plugin k-NN de OpenSearch calcula el limite de memoria no-heap como:
  $$\text{Memoria Nativa} = \text{RAM Fisica (920 MiB)} - \text{Heap JVM (1024 MiB)} = -59.107.328\text{ bytes}$$
  Esto disparaba la excepcion `IllegalArgumentException: Values less than -1 bytes are not supported: -59107328b` provocando la caida del servicio al iniciar.
* **Solucion de Ingenieria:** Se creo una unidad de override en Systemd (`/etc/systemd/system/wazuh-indexer.service.d/override.conf`) con `TimeoutStartSec=600` y un comando `ExecStartPre` con privilegios elevados que inyecta `-Xms512m -Xmx512m` en `/etc/wazuh-indexer/jvm.options`, permitiendo el arranque estable de Wazuh All-in-One con un archivo de intercambio SWAP de 4 GiB sin sobrecostes.

### 5.2. Socket Activation en Ubuntu 24.04 OpenSSH
* **Problema:** En Ubuntu 24.04 LTS, OpenSSH delega la escucha en `ssh.socket`, lo que provoca caidas de sesion y rechazos de conexion durante peticiones concurrentes de ProxyJump a traves del Bastion.
* **Solucion:** Migracion a servicio continuo mediante `systemctl disable --now ssh.socket && systemctl enable --now ssh.service`, complementado con multiplexacion de cliente en `~/.ssh/config` (`ControlMaster auto`, `ControlPersist 10m`).

### 5.3. Restriccion de Version entre Wazuh Manager y Wazuh Agent
* **Problema:** Wazuh Manager 4.9.2 rechaza estrictamente agentes con versiones superiores (`Agent version must be lower or equal to manager version`). El repositorio de APT instalaba la rama generica `4.14`.
* **Solucion:** Fijacion estricta de paquete `wazuh-agent=4.9.2-1` mediante APT pinning y depuracion de etiquetas obsoletas en `ossec.conf` (`users`, `groups`, `services`).

---

## 6. Estructura del Repositorio

```
.
├── .github/
│   └── workflows/
│       └── devsecops.yml        # Flujo CI/CD con Gitleaks, Semgrep, Trivy y Checkov
├── app/                         # Aplicacion web corporativa SaaS en PHP 8.3
│   ├── admin/panel.php          # Panel administrativo de gestion
│   ├── api/v1/                  # Endpoints REST de telemetria climatica
│   ├── config.php               # Capa de conexion a PostgreSQL y configuracion
│   ├── dashboard.php            # Cuadro de mando corporativo
│   └── db_schema.sql            # Esquema de base de datos relacional
├── docs/                        # Documentacion tecnica y evidencias graficas
│   └── evidence/                # Coleccion categorizada de 96 capturas de pantalla
│       ├── phase00_governance/
│       ├── phase01_vpc_and_security_groups/
│       ├── phase03_04_servers_and_hardening/
│       ├── phase06_wazuh_indexer_deployment/
│       ├── phase06_wazuh_suricata_dashboard/
│       └── phase07_devsecops_pipeline/
├── .gitignore                   # Exclusiones de seguridad (claves .pem, credenciales, apuntes)
└── README.md                    # Memoria tecnica central del proyecto
```

---

## 7. Marco Academico e Institucional

* **Institucion:** Escola Palcam (Barcelona)
* **Ciclo Formativo:** Administracio de Sistemes Informatics en Xarxa (ASIX 2)
* **Modulo:** MP14 — Projecte d'Administració de Sistemes Informàtics en Xarxa
* **Autor:** Alejandro De Luque
* **Tutor del Proyecto:** Profesorado del Departamento de Informatica de Escola Palcam
* **Convocatoria:** 2026-2027
