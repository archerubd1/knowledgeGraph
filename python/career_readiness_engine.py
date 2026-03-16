import sys
import mysql.connector
from mysql.connector import Error

def compute_intelligence_metrics(learner_id, target_role_id):
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

            # 2. Fetch all skills REQUIRED by this career role
            # It pulls the skill ID, the importance (weight), and the level required (1-10)
            cursor.execute("""
                SELECT to_node as skill_id, weight, required_level 
                FROM kg_edges 
                WHERE from_node = %s AND relationship_type = 'REQUIRES'
            """, (target_role_id,))
            requirements = cursor.fetchall()

            if not requirements:
                # If no requirements are mapped in the graph, readiness is 0
                print("0.00")
                return

            total_possible_weight = sum(float(r['weight']) for r in requirements)
            earned_weight = 0

            # 3. Clean up old results for this specific sync to prevent duplicate rows
            cursor.execute("""
                DELETE FROM skill_gap_results 
                WHERE learner_id = %s AND target_node = %s
            """, (learner_id, target_role_id))

            # 4. Loop through each required skill to compute Gaps and Readiness
            for req in requirements:
                skill_id = req['skill_id']
                req_lvl = float(req['required_level'])
                weight = float(req['weight'])

                # Fetch learner's current validation for this skill
                cursor.execute("""
                    SELECT validation_score 
                    FROM learner_skill_validation 
                    WHERE learner_id = %s AND skill_node_id = %s
                """, (learner_id, skill_id))
                
                result = cursor.fetchone()
                
                # We assume validation_score is on a 1-10 scale (Expert = 10.0)
                current_lvl = float(result['validation_score']) if result else 0.0
                
                # Readiness Logic: Add to the weighted score (capped at required level)
                # If current_lvl is 10 and req is 10, it adds 100% of the weight
                earned_weight += weight * (min(current_lvl, req_lvl) / req_lvl)

                # Gap Logic: Difference between required and current
                gap_value = max(0, req_lvl - current_lvl)

                # 5. Store individual Skill Gap for the Dashboard Table
                cursor.execute("""
                    INSERT INTO skill_gap_results 
                    (learner_id, target_node, skill_node, gap_value, current_level, required_level, calculated_on)
                    VALUES (%s, %s, %s, %s, %s, %s, NOW())
                """, (learner_id, target_role_id, skill_id, gap_value, current_lvl, req_lvl))

            # 6. FINAL CAREER READINESS SCORE (%)
            readiness_score = round((earned_weight / total_possible_weight) * 100, 2) if total_possible_weight > 0 else 0
            
            # 7. Update the Readiness Gauge table
            cursor.execute("""
                INSERT INTO career_readiness_scores (learner_id, target_node, readiness_score, calculated_on)
                VALUES (%s, %s, %s, NOW())
                ON DUPLICATE KEY UPDATE 
                readiness_score = VALUES(readiness_score), 
                calculated_on = NOW()
            """, (learner_id, target_role_id, readiness_score))

            db.commit()
            
            # Print only the score so the PHP shell_exec can capture it easily
            print(readiness_score)

    except Error as e:
        # If there is a DB error, it will print here
        print(f"Database Error: {e}")
    finally:
        if db and db.is_connected():
            cursor.close()
            db.close()

if __name__ == "__main__":
    # Command line arguments: python career_readiness_engine.py [learner_id] [target_node]
    if len(sys.argv) > 2:
        compute_intelligence_metrics(int(sys.argv[1]), int(sys.argv[2]))
    else:
        print("Error: Missing arguments.")