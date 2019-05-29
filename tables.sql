#USE you_db_name;


CREATE TABLE node_tree (
  idNode int NOT NULL AUTO_INCREMENT,
	level int,
	iLeft int,
	iRight int,
	PRIMARY KEY(idNode)
);


CREATE TABLE node_tree_names (
	idNode int NOT NULL,
	language varchar(7),
	nodeName nvarchar(50),
	FOREIGN KEY (idNode) REFERENCES node_tree(idNode)
);








