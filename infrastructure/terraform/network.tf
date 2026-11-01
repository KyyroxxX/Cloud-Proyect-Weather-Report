# VPC Principal
resource "aws_vpc" "wr_vpc" {
  cidr_block           = var.vpc_cidr
  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = {
    Name = "wr-lab-vpc"
  }
}

# Internet Gateway para la Capa Publica
resource "aws_internet_gateway" "wr_igw" {
  vpc_id = aws_vpc.wr_vpc.id

  tags = {
    Name = "wr-lab-igw"
  }
}

# Subred Publica Web A (Nginx / Bastion / NAT)
resource "aws_subnet" "wr_pub_sub_a" {
  vpc_id                  = aws_vpc.wr_vpc.id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = "${var.aws_region}a"
  map_public_ip_on_launch = true

  tags = {
    Name = "wr-pub-sub-a"
  }
}

# Subred Publica Web B (Alta Disponibilidad Web)
resource "aws_subnet" "wr_pub_sub_b" {
  vpc_id                  = aws_vpc.wr_vpc.id
  cidr_block              = "10.0.2.0/24"
  availability_zone       = "${var.aws_region}b"
  map_public_ip_on_launch = true

  tags = {
    Name = "wr-pub-sub-b"
  }
}

# Subred Privada Aplicacion A (PHP Telemetria Backend)
resource "aws_subnet" "wr_app_sub_a" {
  vpc_id                  = aws_vpc.wr_vpc.id
  cidr_block              = "10.0.11.0/24"
  availability_zone       = "${var.aws_region}a"
  map_public_ip_on_launch = false

  tags = {
    Name = "wr-app-sub-a"
  }
}

# Subred Privada Aplicacion B (Alta Disponibilidad App)
resource "aws_subnet" "wr_app_sub_b" {
  vpc_id                  = aws_vpc.wr_vpc.id
  cidr_block              = "10.0.12.0/24"
  availability_zone       = "${var.aws_region}b"
  map_public_ip_on_launch = false

  tags = {
    Name = "wr-app-sub-b"
  }
}

# Subred Privada Base de Datos A (PostgreSQL / Redis)
resource "aws_subnet" "wr_data_sub_a" {
  vpc_id                  = aws_vpc.wr_vpc.id
  cidr_block              = "10.0.21.0/24"
  availability_zone       = "${var.aws_region}a"
  map_public_ip_on_launch = false

  tags = {
    Name = "wr-data-sub-a"
  }
}

# Subred Privada Seguridad A (SIEM Wazuh All-in-One)
resource "aws_subnet" "wr_sec_sub_a" {
  vpc_id                  = aws_vpc.wr_vpc.id
  cidr_block              = "10.0.31.0/24"
  availability_zone       = "${var.aws_region}a"
  map_public_ip_on_launch = false

  tags = {
    Name = "wr-sec-sub-a"
  }
}

# Tabla de Enrutamiento Publica
resource "aws_route_table" "wr_public_rt" {
  vpc_id = aws_vpc.wr_vpc.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.wr_igw.id
  }

  tags = {
    Name = "wr-route-public"
  }
}

# Asociaciones de Subredes Publicas
resource "aws_route_table_association" "pub_a_assoc" {
  subnet_id      = aws_subnet.wr_pub_sub_a.id
  route_table_id = aws_route_table.wr_public_rt.id
}

resource "aws_route_table_association" "pub_b_assoc" {
  subnet_id      = aws_subnet.wr_pub_sub_b.id
  route_table_id = aws_route_table.wr_public_rt.id
}

# Tabla de Enrutamiento Privada (Aislada por defecto o ruteada via NAT Instance)
resource "aws_route_table" "wr_private_rt" {
  vpc_id = aws_vpc.wr_vpc.id

  tags = {
    Name = "wr-route-private"
  }
}

# Ruta de Salida a Internet via NAT Instance (wr-lab-nginx) a $0.00
resource "aws_route" "wr_private_nat_route" {
  count                  = var.enable_nat_routing ? 1 : 0
  route_table_id         = aws_route_table.wr_private_rt.id
  destination_cidr_block = "0.0.0.0/0"
  network_interface_id   = aws_instance.wr_lab_nginx.primary_network_interface_id
}

# Asociaciones de Subredes Privadas
resource "aws_route_table_association" "app_a_assoc" {
  subnet_id      = aws_subnet.wr_app_sub_a.id
  route_table_id = aws_route_table.wr_private_rt.id
}

resource "aws_route_table_association" "app_b_assoc" {
  subnet_id      = aws_subnet.wr_app_sub_b.id
  route_table_id = aws_route_table.wr_private_rt.id
}

resource "aws_route_table_association" "data_a_assoc" {
  subnet_id      = aws_subnet.wr_data_sub_a.id
  route_table_id = aws_route_table.wr_private_rt.id
}

resource "aws_route_table_association" "sec_a_assoc" {
  subnet_id      = aws_subnet.wr_sec_sub_a.id
  route_table_id = aws_route_table.wr_private_rt.id
}
