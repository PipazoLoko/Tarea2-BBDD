# Tarea 2 - PHP y MySQL

- Felipe Carrasco Patelli - 202473522-5
- Felipe Luengo Bascuñan - 202473509-8

# Instalación
Para ejecutar el programa, hay que tener instalado:
- MySQL 8+
- PHP 

1. Configurar conexión a la base de datos en `/PHP/config.php`
2. Crear la base de datos e importar el DDL:
```bash
mysql -u root -p < "./BD/create.sql
```
3. Ejecutar el script para poblar la base de datos:
```bash
# Asegurarse de configurar los datos de la base de datos
# y tener la librería "mysql-connector-python"
python populate.py
```

4. Iniciar el servidor PHP para pruebas:
```bash
php -S localhost:8000 -t ".../tarea_2/PHP"
```
5. Abrir http://localhost:8000/index.php
