# Obtener la AMI mas reciente de Ubuntu 24.04 LTS (Noble Numbat)
data "aws_ami" "ubuntu_24_04" {
  most_recent = true

  filter {
    name   = "name"
    values = ["ubuntu/images/hvm-ssd-gp3/ubuntu-noble-24.04-amd64-server-*"]
  }

  filter {
    name   = "virtualization-type"
    values = ["hvm"]
  }

  owners = ["099720109477"] # Canonical
}

# 1. Servidor Web Nginx, Bastion Host y NAT Instance ($0.00)
resource "aws_instance" "wr_lab_nginx" {
  ami                         = data.aws_ami.ubuntu_24_04.id
  instance_type               = var.instance_type_web
  subnet_id                   = aws_subnet.wr_pub_sub_a.id
  private_ip                  = "10.0.1.10"
  associate_public_ip_address = true
  vpc_security_group_ids      = [aws_security_group.wr_sg_nginx.id]
  key_name                    = var.key_name
  source_dest_check           = false # Imprescindible para enrutar trafico NAT de las subredes privadas

  root_block_device {
    volume_size           = 8
    volume_type           = "gp3"
    encrypted             = true
    delete_on_termination = true
  }

  user_data = <<-EOF
              #!/bin/bash
              set -e
              # 1. Habilitar reenvio de paquetes IPv4
              echo "net.ipv4.ip_forward=1" >> /etc/sysctl.d/99-nat.conf
              sysctl -p /etc/sysctl.d/99-nat.conf
              
              # 2. Configurar enmascaramiento NAT dinamico con la interfaz principal
              IFACE=$(ip route | awk '/default/ {print $5}')
              iptables -t nat -A POSTROUTING -o $IFACE -j MASQUERADE
              
              # 3. Instalar paquetes base y persistir reglas iptables
              export DEBIAN_FRONTEND=noninteractive
              apt-get update -y
              apt-get install -y iptables-persistent nginx
              netfilter-persistent save
              systemctl enable --now nginx
              EOF

  tags = {
    Name = "wr-lab-nginx"
    Role = "BastionWebNAT"
  }
}

# 2. Servidor de Aplicacion PHP Telemetria (Subred Privada)
resource "aws_instance" "wr_lab_app" {
  ami                         = data.aws_ami.ubuntu_24_04.id
  instance_type               = var.instance_type_app
  subnet_id                   = aws_subnet.wr_app_sub_a.id
  private_ip                  = "10.0.11.10"
  associate_public_ip_address = false
  vpc_security_group_ids      = [aws_security_group.wr_sg_app.id]
  key_name                    = var.key_name

  root_block_device {
    volume_size           = 8
    volume_type           = "gp3"
    encrypted             = true
    delete_on_termination = true
  }

  tags = {
    Name = "wr-lab-app"
    Role = "ApplicationBackend"
  }

  depends_on = [aws_route.wr_private_nat_route]
}

# 3. Servidor de Base de Datos PostgreSQL y Redis (Subred Privada)
resource "aws_instance" "wr_lab_data" {
  ami                         = data.aws_ami.ubuntu_24_04.id
  instance_type               = var.instance_type_data
  subnet_id                   = aws_subnet.wr_data_sub_a.id
  private_ip                  = "10.0.21.10"
  associate_public_ip_address = false
  vpc_security_group_ids      = [aws_security_group.wr_sg_data.id]
  key_name                    = var.key_name

  root_block_device {
    volume_size           = 8
    volume_type           = "gp3"
    encrypted             = true
    delete_on_termination = true
  }

  tags = {
    Name = "wr-lab-data"
    Role = "DatabaseBackend"
  }

  depends_on = [aws_route.wr_private_nat_route]
}

# 4. Servidor SIEM Wazuh All-in-One (Subred Privada de Seguridad)
resource "aws_instance" "wr_lab_wazuh" {
  ami                         = data.aws_ami.ubuntu_24_04.id
  instance_type               = var.instance_type_wazuh
  subnet_id                   = aws_subnet.wr_sec_sub_a.id
  private_ip                  = "10.0.31.10"
  associate_public_ip_address = false
  vpc_security_group_ids      = [aws_security_group.wr_sg_wazuh.id]
  key_name                    = var.key_name

  # Almacenamiento GP3 de 25GB dentro del limite gratuito de 30GB de AWS
  root_block_device {
    volume_size           = 25
    volume_type           = "gp3"
    encrypted             = true
    delete_on_termination = true
  }

  # SWAP de 4GB para ejecutar Wazuh en t3.micro (1GB RAM) con estabilidad y $0.00 de coste
  user_data = <<-EOF
              #!/bin/bash
              set -e
              fallocate -l 4G /swapfile
              chmod 600 /swapfile
              mkswap /swapfile
              swapon /swapfile
              echo '/swapfile none swap sw 0 0' >> /etc/fstab
              EOF

  tags = {
    Name = "wr-lab-wazuh"
    Role = "SIEMSecurity"
  }

  depends_on = [aws_route.wr_private_nat_route]
}
