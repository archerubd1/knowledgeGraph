import sys
import mysql.connector

learner_id = int(sys.argv[1])
target_node = int(sys.argv[2])

db = mysql.connector.connect(...)
cursor = db.cursor(dictionary=True)

cursor.execute("""
SELECT to_node, required_level
FROM kg_edges
WHERE from_node=%s
AND relationship_type='REQUIRES'
""",(target_node,))

required = cursor.fetchall()

cursor.execute("""
DELETE FROM skill_gap_results
WHERE learner_id=%s AND target_node=%s
""",(learner_id,target_node))

for skill in required:

    cursor.execute("""
    SELECT validation_score
    FROM learner_skill_validation
    WHERE learner_id=%s
    AND skill_node_id=%s
    """,(learner_id,skill['to_node']))

    result = cursor.fetchone()
    validated = result['validation_score'] if result else 0
    gap = skill['required_level'] - validated

    if gap > 0:
        cursor.execute("""
        INSERT INTO skill_gap_results
        VALUES (%s,%s,%s,%s,NOW())
        """,(learner_id,target_node,skill['to_node'],gap))

db.commit()
db.close()
