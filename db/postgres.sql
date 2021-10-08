CREATE TABLE tipousuario (
        codtipousuario        SERIAL PRIMARY KEY,
        descricao     VARCHAR(100)
);


CREATE TABLE aluno (
        codaluno SERIAL PRIMARY KEY,
        codtipousuario INTEGER NOT NULL,
        nome  VARCHAR(200),
        email VARCHAR(200) UNIQUE NOT NULL,
        senha VARCHAR(200),
		alterasenha BOOLEAN,
        observacao    TEXT,
		FOREIGN KEY (codtipousuario) REFERENCES tipousuario (codtipousuario)
);

CREATE TABLE curso (
        codcurso      SERIAL PRIMARY KEY,
        descricao     VARCHAR(200),
        sigla VARCHAR(10),
        observacao    TEXT
);

CREATE TABLE tarefa (
        codtarefa     SERIAL PRIMARY KEY,
        descricao     VARCHAR(200),
        sigla VARCHAR(10),
        instrucoes    TEXT,
        observacao    TEXT
);

CREATE TABLE turma (
        codturma      SERIAL PRIMARY KEY,
        codcurso      INTEGER NOT NULL,
        descricao     VARCHAR(200),
        sigla VARCHAR(10),
        observacao    TEXT,
        FOREIGN KEY(codcurso) REFERENCES curso(codcurso)
);

CREATE TABLE tarefaturma (
        codtarefaturma        SERIAL PRIMARY KEY,
        codtarefa     INTEGER NOT NULL,
        codturma      INTEGER NOT NULL,
        datainicio    DATE,
        datafim       DATE,
        observacao    TEXT,
        FOREIGN KEY(codturma) REFERENCES turma(codturma),
        FOREIGN KEY(codtarefa) REFERENCES tarefa(codtarefa)
);

CREATE TABLE tarefaturmaaluno (
        codtarefaturmaaluno   SERIAL PRIMARY KEY,
        codtarefaturma        INTEGER NOT NULL,
        codaluno      INTEGER NOT NULL,
        dataentrega   DATE,
        entregas      INTEGER DEFAULT 0,
        resultados    TEXT,
        nota  INTEGER,
        observacao    TEXT,
        FOREIGN KEY(codaluno) REFERENCES aluno(codaluno),
        FOREIGN KEY(codtarefaturma) REFERENCES tarefaturma(codtarefaturma)
);



CREATE TABLE turmaaluno (
        codturmaaluno SERIAL PRIMARY KEY,
        codturma      INTEGER NOT NULL,
        codaluno      INTEGER NOT NULL,
        FOREIGN KEY(codturma) REFERENCES turma(codturma),
        UNIQUE(codturma,codaluno),
        FOREIGN KEY(codaluno) REFERENCES aluno(codaluno)
);