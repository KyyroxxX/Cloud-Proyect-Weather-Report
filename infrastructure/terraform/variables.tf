variable "aws_region" {
  description = "Region de AWS para el despliegue del laboratorio"
  type        = string
  default     = "eu-west-1"
}

variable "vpc_cidr" {
  description = "Bloque CIDR de la VPC principal"
  type        = string
  default     = "10.0.0.0/16"
}

variable "key_name" {
  description = "Nombre del par de claves SSH en AWS (wr-ssh-key)"
  type        = string
  default     = "wr-ssh-key"
}

variable "admin_ip" {
  description = "Direccion IP publica del administrador para acceso SSH y WAF/Dashboard (ej. 85.32.100.50/32 o 0.0.0.0/0)"
  type        = string
  default     = "0.0.0.0/0"
}

variable "instance_type_web" {
  description = "Tipo de instancia EC2 para el Servidor Web / Bastion / NAT (wr-lab-nginx)"
  type        = string
  default     = "t2.micro"
}

variable "instance_type_app" {
  description = "Tipo de instancia EC2 para el Servidor de Aplicacion PHP (wr-lab-app)"
  type        = string
  default     = "t2.micro"
}

variable "instance_type_data" {
  description = "Tipo de instancia EC2 para la Base de Datos PostgreSQL/Redis (wr-lab-data)"
  type        = string
  default     = "t2.micro"
}

variable "instance_type_wazuh" {
  description = "Tipo de instancia EC2 para el Servidor SIEM Wazuh All-in-One (wr-lab-wazuh)"
  type        = string
  default     = "t3.micro"
}

variable "enable_nat_routing" {
  description = "Habilitar enrutamiento de salida a Internet para subredes privadas a traves de wr-lab-nginx"
  type        = bool
  default     = true
}
