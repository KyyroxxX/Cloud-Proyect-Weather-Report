# Modulo Ansible: Automatizacion y Hardening de Servidores

> [!NOTE] Proposito del Modulo
> Automatizar la configuracion del sistema operativo, el bastionado basico (UFW, Fail2ban, sysctl) y los servicios requeridos (Nginx, PHP, PostgreSQL, Redis) en los nodos aprovisionados por Terraform.

---

## 1. Prerrequisitos

- Terraform ha completado el despliegue de las instancias EC2.
- El archivo de clave privada `~/.ssh/wr-ssh-key.pem` tiene permisos restringidos (`chmod 400`).
- Ansible esta instalado en tu maquina local o subsistema WSL.

---

## 2. Preparar el Inventario

Copia el inventario de ejemplo:

```bash
cp inventory.ini.example inventory.ini
```

Edita `inventory.ini` y sustituye `<IP_PUBLICA_NGINX>` por el valor obtenido en los outputs de Terraform (`bastion_public_ip`).

---

## 3. Comprobacion de Conectividad (Ping)

Verifica que Ansible puede comunicarse con todos los servidores a traves del Bastion Host:

```bash
ansible all -i inventory.ini -m ping
```

Salida esperada:
```text
wr-nginx | SUCCESS => {"changed": false, "ping": "pong"}
wr-app   | SUCCESS => {"changed": false, "ping": "pong"}
wr-data  | SUCCESS => {"changed": false, "ping": "pong"}
wr-wazuh | SUCCESS => {"changed": false, "ping": "pong"}
```

---

## 4. Ejecutar el Playbook

Aplica la configuracion y el bastionado a todos los servidores:

```bash
ansible-playbook -i inventory.ini site.yml
```
