CREATE TABLE tipousuario (
        codtipousuario        SERIAL PRIMARY KEY,
        descricao     VARCHAR(100)
);

CREATE TABLE usuario (
        codusuario SERIAL PRIMARY KEY,
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

CREATE TABLE plugin (
        codplugin      SERIAL PRIMARY KEY,
        descricao     VARCHAR(200),
        retorno       BOOLEAN,
        observacao    TEXT
);

CREATE TABLE tarefa (
        codtarefa     SERIAL PRIMARY KEY,
        codplugin     INT NOT NULL,
        descricao     VARCHAR(200),
        sigla VARCHAR(10),
        instrucoes    TEXT,
        observacao    TEXT,
		FOREIGN KEY (codplugin) REFERENCES plugin (codplugin)
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

CREATE TABLE tarefaturmausuario (
        codtarefaturmausuario   SERIAL PRIMARY KEY,
        codtarefaturma        INTEGER NOT NULL,
        codusuario      INTEGER NOT NULL,
        dataentrega   DATE,
        entregas      INTEGER DEFAULT 0,
        resultados    TEXT,
        nota  INTEGER,
        notafinal INTEGER,
        observacao    TEXT,
        FOREIGN KEY(codusuario) REFERENCES usuario(codusuario),
        FOREIGN KEY(codtarefaturma) REFERENCES tarefaturma(codtarefaturma)
);

CREATE TABLE turmausuario (
        codturmausuario SERIAL PRIMARY KEY,
        codturma      INTEGER NOT NULL,
        codusuario      INTEGER NOT NULL,
        FOREIGN KEY(codturma) REFERENCES turma(codturma),
        UNIQUE(codturma,codusuario),
        FOREIGN KEY(codusuario) REFERENCES usuario(codusuario)
);

INSERT INTO tipousuario (descricao) VALUES ('Administrador');
INSERT INTO tipousuario (descricao) VALUES ('Professor');
INSERT INTO tipousuario (descricao) VALUES ('Assistente');
INSERT INTO tipousuario (descricao) VALUES ('Aluno');
INSERT INTO usuario (codtipousuario, nome, email, senha, alterasenha) VALUES (1, 'admin', 'admin', '21232f297a57a5a743894a0e4a801fc3', true);