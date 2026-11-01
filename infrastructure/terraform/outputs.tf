output "bastion_public_ip" {
  description = "Direccion IP publica del Bastion Host y Reverse Proxy Nginx"
  value       = aws_instance.wr_lab_nginx.public_ip
}

output "nginx_public_url" {
  description = "URL publica para validar Nginx"
  value       = "http://${aws_instance.wr_lab_nginx.public_ip}"
}

output "app_private_ip" {
  description = "IP privada del servidor de aplicaciones PHP"
  value       = aws_instance.wr_lab_app.private_ip
}

output "data_private_ip" {
  description = "IP privada del servidor de base de datos PostgreSQL/Redis"
  value       = aws_instance.wr_lab_data.private_ip
}

output "wazuh_private_ip" {
  description = "IP privada del servidor SIEM Wazuh All-in-One"
  value       = aws_instance.wr_lab_wazuh.private_ip
}

output "ssh_bastion_command" {
  description = "Comando para conectar al Bastion Host por SSH"
  value       = "ssh -i ~/.ssh/${var.key_name}.pem ubuntu@${aws_instance.wr_lab_nginx.public_ip}"
}

output "ssh_wazuh_dashboard_tunnel" {
  description = "Comando SSH LocalForward para acceder al Dashboard de Wazuh (https://localhost:8443) con Zero Trust"
  value       = "ssh -i ~/.ssh/${var.key_name}.pem -J ubuntu@${aws_instance.wr_lab_nginx.public_ip} -L 8443:10.0.31.10:443 ubuntu@10.0.31.10"
}

output "ssh_config_snippet" {
  description = "Fragmento para anadir a ~/.ssh/config o C:\\Users\\Usuario\\.ssh\\config"
  value       = <<-EOT
    Host wr-bastion
        HostName ${aws_instance.wr_lab_nginx.public_ip}
        User ubuntu
        IdentityFile ~/.ssh/${var.key_name}.pem
        StrictHostKeyChecking no

    Host wr-app
        HostName 10.0.11.10
        User ubuntu
        IdentityFile ~/.ssh/${var.key_name}.pem
        ProxyJump wr-bastion
        StrictHostKeyChecking no

    Host wr-data
        HostName 10.0.21.10
        User ubuntu
        IdentityFile ~/.ssh/${var.key_name}.pem
        ProxyJump wr-bastion
        StrictHostKeyChecking no

    Host wr-wazuh
        HostName 10.0.31.10
        User ubuntu
        IdentityFile ~/.ssh/${var.key_name}.pem
        ProxyJump wr-bastion
        LocalForward 8443 10.0.31.10:443
        StrictHostKeyChecking no
  EOT
}
