CREATE TABLE kg_nodes (
  node_id INT AUTO_INCREMENT PRIMARY KEY,
  node_type VARCHAR(50),
  node_name VARCHAR(255),
  taxonomy_level INT,
  parent_node INT NULL,
  description TEXT,
  created_on DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kg_edges (
  edge_id INT AUTO_INCREMENT PRIMARY KEY,
  from_node INT,
  to_node INT,
  relationship_type VARCHAR(100),
  weight FLOAT DEFAULT 1.0,
  required_level FLOAT DEFAULT 1.0,
  confidence FLOAT DEFAULT 1.0,
  created_on DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE learner_skill_validation (
  learner_id INT,
  skill_node_id INT,
  validation_type VARCHAR(50),
  validation_score FLOAT,
  validated_on DATETIME
);

CREATE TABLE career_readiness_scores (
  learner_id INT,
  career_node INT,
  readiness_score FLOAT,
  calculated_on DATETIME
);

CREATE TABLE skill_gap_results (
  learner_id INT,
  target_node INT,
  skill_node INT,
  gap_value FLOAT,
  calculated_on DATETIME
);

CREATE TABLE graph_execution_log (
  execution_type VARCHAR(50),
  executed_on DATETIME,
  status VARCHAR(50)
);
