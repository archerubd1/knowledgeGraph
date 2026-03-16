import sys
import mysql.connector

def calculate_readiness(user_id, career_id):
    db = mysql.connector.connect(host="localhost", user="root", password="root", database="astraal_lxp")
    cursor = db.cursor(dictionary=True)

    # 1. Fetch requirements for this career
    cursor.execute("SELECT to_node, weight, required_level FROM kg_edges WHERE from_node=%s", (career_id,))
    requirements = cursor.fetchall()
    
    if not requirements: return 0.0

    total_weight = sum(r['weight'] for r in requirements)
    earned_score = 0

    # 2. Check user's validated skill levels
    for req in requirements:
        cursor.execute("SELECT validation_score FROM learner_skill_validation WHERE learner_id=%s AND skill_node_id=%s", 
                       (user_id, req['to_node']))
        result = cursor.fetchone()
        if result:
            # Score is weighted by the importance of the skill to that career
            earned_score += result['validation_score'] * req['weight']

    return round(earned_score / total_weight, 2)

if __name__ == "__main__":
    # Called by PHP: python engine.py [user_id] [career_id]
    print(calculate_readiness(sys.argv[1], sys.argv[2]))