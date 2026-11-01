# Security Group: Reverse Proxy Nginx & Bastion Host
resource "aws_security_group" "wr_sg_nginx" {
  name        = "wr-sg-nginx"
  description = "Trafico web publico HTTP/HTTPS y acceso SSH de administracion"
  vpc_id      = aws_vpc.wr_vpc.id

  ingress {
    description = "Trafico HTTP web"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "Trafico HTTPS cifrado TLS"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "SSH Administrador"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = [var.admin_ip]
  }

  egress {
    description = "Salida total para descarga de paquetes y reenvio NAT"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "wr-sg-nginx"
  }
}

# Security Group: Servidor de Aplicacion PHP
resource "aws_security_group" "wr_sg_app" {
  name        = "wr-sg-app"
  description = "Trafico app PHP solo desde Nginx proxy y Bastion"
  vpc_id      = aws_vpc.wr_vpc.id

  ingress {
    description     = "HTTP Backend PHP-FPM / Microservicios"
    from_port       = 8080
    to_port         = 8080
    protocol        = "tcp"
    security_groups = [aws_security_group.wr_sg_nginx.id]
  }

  ingress {
    description     = "SSH Bastion Jump"
    from_port       = 22
    to_port         = 22
    protocol        = "tcp"
    security_groups = [aws_security_group.wr_sg_nginx.id]
  }

  egress {
    description = "Salida general para paquetes via NAT, conexion BD y agentes Wazuh"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "wr-sg-app"
  }
}

# Security Group: Capa de Base de Datos (PostgreSQL y Redis)
resource "aws_security_group" "wr_sg_data" {
  name        = "wr-sg-data"
  description = "Base de datos y cache protegida accesible unicamente desde App y Bastion"
  vpc_id      = aws_vpc.wr_vpc.id

  ingress {
    description     = "PostgreSQL desde Servidor de Aplicacion"
    from_port       = 5432
    to_port         = 5432
    protocol        = "tcp"
    security_groups = [aws_security_group.wr_sg_app.id]
  }

  ingress {
    description     = "Redis Cache desde Servidor de Aplicacion"
    from_port       = 6379
    to_port         = 6379
    protocol        = "tcp"
    security_groups = [aws_security_group.wr_sg_app.id]
  }

  ingress {
    description     = "SSH Bastion Jump"
    from_port       = 22
    to_port         = 22
    protocol        = "tcp"
    security_groups = [aws_security_group.wr_sg_nginx.id]
  }

  egress {
    description = "Salida para actualizaciones y envio de logs al SIEM Wazuh"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "wr-sg-data"
  }
}

# Security Group: SIEM Wazuh Manager y Agentes
resource "aws_security_group" "wr_sg_wazuh" {
  name        = "wr-sg-wazuh"
  description = "Recepcion de telemetria de agentes SIEM y acceso administrativo"
  vpc_id      = aws_vpc.wr_vpc.id

  ingress {
    description = "Dashboard Web Wazuh HTTPS desde IP de administracion"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = [var.admin_ip]
  }

  ingress {
    description = "Trafico de eventos desde agentes Wazuh en la VPC"
    from_port   = 1514
    to_port     = 1514
    protocol    = "tcp"
    cidr_blocks = [var.vpc_cidr]
  }

  ingress {
    description = "Registro de nuevos agentes Wazuh en la VPC"
    from_port   = 1515
    to_port     = 1515
    protocol    = "tcp"
    cidr_blocks = [var.vpc_cidr]
  }

  ingress {
    description = "API REST Wazuh Manager desde IP de administracion"
    from_port   = 55000
    to_port     = 55000
    protocol    = "tcp"
    cidr_blocks = [var.admin_ip]
  }

  ingress {
    description     = "SSH Bastion Jump"
    from_port       = 22
    to_port         = 22
    protocol        = "tcp"
    security_groups = [aws_security_group.wr_sg_nginx.id]
  }

  egress {
    description = "Salida a repositorios de Wazuh y reglas Suricata"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "wr-sg-wazuh"
  }
}
