# Modulo Terraform: Weather Report Cloud Security Infrastructure

> [!IMPORTANT] Proposito del Modulo
> Este directorio contiene el codigo HCL completo y declarativo para aprovisionar, reproducir o destruir en minutos toda la infraestructura de red, seguridad y servidores del laboratorio Weather Report en la region `eu-west-1` de AWS.

---

## 1. Arquitectura Codificada

- **VPC (`10.0.0.0/16`)**: Red aislada con soporte DNS completo.
- **Internet Gateway (`wr-lab-igw`)**: Punto de salida a Internet para la capa publica.
- **Subredes (6)**:
  - `wr-pub-sub-a` (`10.0.1.0/24`) y `wr-pub-sub-b` (`10.0.2.0/24`): Capa publica web.
  - `wr-app-sub-a` (`10.0.11.0/24`) y `wr-app-sub-b` (`10.0.12.0/24`): Capa de aplicacion privada.
  - `wr-data-sub-a` (`10.0.21.0/24`): Capa de base de datos privada.
  - `wr-sec-sub-a` (`10.0.31.0/24`): Capa de seguridad y SIEM privada.
- **Enrutamiento Inteligente a $0.00**:
  - Subredes publicas enlazadas a Internet Gateway.
  - Subredes privadas enrutadas a traves de la instancia `wr-lab-nginx` actuando como NAT Instance con IP Masquerade, evitando los 32 USD/mes de un AWS NAT Gateway gestionado.
- **Grupos de Seguridad (4)**:
  - `wr-sg-nginx`: Puertos 80, 443 publicos y puerto 22 para administracion.
  - `wr-sg-app`: Puerto 8080 y 22 permitidos exclusivamente desde `wr-sg-nginx`.
  - `wr-sg-data`: Puertos 5432 (PostgreSQL) y 6379 (Redis) permitidos unicamente desde `wr-sg-app`.
  - `wr-sg-wazuh`: Puertos 1514 y 1515 para agentes en la VPC, 443 (Dashboard) y 55000 (API) para administracion.
- **Instancias EC2 (4)**:
  - `wr-lab-nginx` (`t2.micro`): Bastion Host, Nginx Proxy y NAT Gateway.
  - `wr-lab-app` (`t2.micro`): Backend PHP con IP fija `10.0.11.10`.
  - `wr-lab-data` (`t2.micro`): Base de datos con IP fija `10.0.21.10`.
  - `wr-lab-wazuh` (`t3.micro`): SIEM Wazuh All-in-One con IP fija `10.0.31.10`, 25 GB GP3 y 4 GB de memoria virtual SWAP.

---

## 2. Flujo de Trabajo Seguro de Despliegue

### Paso 1: Inicializar Terraform
```bash
terraform init
```

### Paso 2: Validar Sintaxis
```bash
terraform validate
```

### Paso 3: Generar Plan de Ejecucion
```bash
terraform plan -out=tfplan
```

### Paso 4: Aplicar el Plan
```bash
terraform apply "tfplan"
```

---

## 3. Destruccion Total de Recursos ($0.00 Base)

Para apagar y eliminar absolutamente todos los recursos gestionados y dejar la cuenta de AWS a coste cero:

```bash
terraform destroy
```

Escribe `yes` cuando lo solicite. En aproximadamente 2 minutos la VPC, subredes, tablas, grupos de seguridad e instancias seran eliminados.
