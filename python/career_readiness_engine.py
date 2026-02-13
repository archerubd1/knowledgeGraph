import sys
import mysql.connector

learner_id = int(sys.argv[1])
career_node = int(sys.argv[2])

db = mysql.connector.connect(...)
cursor = db.cursor(dictionary=True)

cursor.execute("""
SELECT to_node, weight
FROM kg_edges
WHERE from_node=%s
AND relationship_type='REQUIRES'
""",(career_node,))

required = cursor.fetchall()

total_weight = sum(r['weight'] for r in required)
earned_weight = 0

for skill in required:

    cursor.execute("""
    SELECT validation_score
    FROM learner_skill_validation
    WHERE learner_id=%s
    AND skill_node_id=%s
    """,(learner_id,skill['to_node']))

    result = cursor.fetchone()
    if result:
        earned_weight += skill['weight'] * result['validation_score']

readiness = round(earned_weight / total_weight,2)

cursor.execute("""
REPLACE INTO career_readiness_scores
VALUES (%s,%s,%s,NOW())
""",(learner_id,career_node,readiness))

db.commit()
db.close()
