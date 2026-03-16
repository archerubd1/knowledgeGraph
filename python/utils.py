import mysql.connector

def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="root", # Match your PHP pass
        database="astraal_lxp"
    )