-- 1. Core User Table (Added for Registration/Login)
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL, -- Store plain text for prototype, hash for production
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Knowledge Graph Nodes (Careers, Skills, Courses)
CREATE TABLE kg_nodes (
  node_id INT AUTO_INCREMENT PRIMARY KEY,
  node_type VARCHAR(50), -- e.g., 'Skill', 'Career', 'Course'
  node_name VARCHAR(255),
  taxonomy_level INT,
  parent_node INT NULL,
  description TEXT,
  created_on DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Relationships (Edges)
CREATE TABLE kg_edges (
  edge_id INT AUTO_INCREMENT PRIMARY KEY,
  from_node INT,
  to_node INT,
  relationship_type VARCHAR(100), -- e.g., 'requires', 'builds'
  weight FLOAT DEFAULT 1.0,
  required_level FLOAT DEFAULT 1.0,
  confidence FLOAT DEFAULT 1.0,
  created_on DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Learner Skill Data (Linked to users.user_id)
CREATE TABLE learner_skill_validation (
  learner_id INT,
  skill_node_id INT,
  validation_type VARCHAR(50),
  validation_score FLOAT,
  validated_on DATETIME,
  FOREIGN KEY (learner_id) REFERENCES users(user_id)
);

-- 5. Career Readiness Scores
CREATE TABLE career_readiness_scores (
  learner_id INT,
  career_node INT,
  readiness_score FLOAT,
  calculated_on DATETIME,
  FOREIGN KEY (learner_id) REFERENCES users(user_id)
);

-- 6. Skill Gap Analysis Results
CREATE TABLE skill_gap_results (
  learner_id INT,
  target_node INT,
  skill_node INT,
  gap_value FLOAT,
  calculated_on DATETIME,
  FOREIGN KEY (learner_id) REFERENCES users(user_id)
);

-- 7. System Execution Logs
CREATE TABLE graph_execution_log (
  execution_type VARCHAR(50),
  executed_on DATETIME,
  status VARCHAR(50)
);