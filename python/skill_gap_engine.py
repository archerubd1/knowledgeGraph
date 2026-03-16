import sys
import mysql.connector
from mysql.connector import Error

def compute_intelligence_metrics(learner_id, target_node):
    db = None
    try:
        # 1. Database Connection
        db = mysql.connector.connect(
            host="localhost",
            user="root",
            password="root",
            database="astraal_lxp" 
        )
        
        if db.is_connected():
            cursor = db.cursor(dictionary=True)

            # 2. Fetch required skills
            cursor.execute("""
                SELECT to_node, required_level 
                FROM kg_edges 
                WHERE from_node = %s AND relationship_type = 'REQUIRES'
            """, (target_node,))
            requirements = cursor.fetchall()

            if not requirements:
                print(f"No requirements found for node {target_node}")
                return

            # 3. Clean up old skill gap results for this specific target
            cursor.execute("""
                DELETE FROM skill_gap_results 
                WHERE learner_id = %s AND target_node = %s
            """, (learner_id, target_node))

            total_required_points = 0
            total_validated_points = 0

            # 4. Loop through each required skill
            for skill in requirements:
                skill_id = skill['to_node']
                req_lvl = float(skill['required_level'])
                total_required_points += req_lvl

                # Check learner's proficiency (matches your table learner_skill_validation)
                cursor.execute("""
                    SELECT validation_score 
                    FROM learner_skill_validation 
                    WHERE learner_id = %s AND skill_node_id = %s
                """, (learner_id, skill_id))
                
                result = cursor.fetchone()
                current_lvl = float(result['validation_score']) if result else 0.0
                total_validated_points += min(current_lvl, req_lvl)

                gap_value = max(0, req_lvl - current_lvl)

                # 5. Store individual Skill Gap
                cursor.execute("""
                    INSERT INTO skill_gap_results 
                    (learner_id, target_node, skill_node, gap_value, required_level, current_level, calculated_on)
                    VALUES (%s, %s, %s, %s, %s, %s, NOW())
                """, (learner_id, target_node, skill_id, gap_value, req_lvl, current_lvl))

            # 6. COMPUTE CAREER READINESS SCORE
            readiness_score = (total_validated_points / total_required_points) if total_required_points > 0 else 0
            
            # 7. Store Score (Using target_node column)
            # This is where the "Unknown Column" error happens if Step 1 isn't done!
            cursor.execute("""
                INSERT INTO career_readiness_scores (learner_id, target_node, readiness_score, calculated_on)
                VALUES (%s, %s, %s, NOW())
                ON DUPLICATE KEY UPDATE 
                readiness_score = VALUES(readiness_score), 
                calculated_on = NOW()
            """, (learner_id, target_node, readiness_score))

            db.commit()
            print(f"Success: Readiness Score calculated at {round(readiness_score * 100, 2)}%")

    except Error as e:
        print(f"Database Error: {e}")
    finally:
        if db and db.is_connected():
            cursor.close()
            db.close()

if __name__ == "__main__":
    if len(sys.argv) > 2:
        compute_intelligence_metrics(int(sys.argv[1]), int(sys.argv[2]))
    else:
        print("Error: Missing learner_id or target_node_id arguments.")