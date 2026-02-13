import mysql.connector

def get_neighbors(node_id):

    db = mysql.connector.connect(
        host="localhost",
        user="db_user",
        password="db_password",
        database="astraal_lxp"
    )

    cursor = db.cursor(dictionary=True)

    cursor.execute("""
    SELECT to_node FROM kg_edges WHERE from_node=%s
    """,(node_id,))

    neighbors = [r['to_node'] for r in cursor.fetchall()]
    db.close()
    return neighbors
