CREATE TABLE IF NOT EXISTS "aluno" (
        "codaluno"      INTEGER,
        "codtipousuario"        INTEGER DEFAULT 4,
        "nome"  TEXT,
        "email" TEXT,
        "senha" TEXT,
        "observacao"    TEXT,
        PRIMARY KEY("codaluno" AUTOINCREMENT),
        UNIQUE("email")
);

CREATE TABLE IF NOT EXISTS "curso" (
        "codcurso"      INTEGER,
        "descricao"     TEXT,
        "sigla" TEXT,
        "observacao"    TEXT,
        PRIMARY KEY("codcurso" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "tarefa" (
        "codtarefa"     INTEGER,
        "descricao"     TEXT,
        "sigla" TEXT,
        "instrucoes"    TEXT,
        "observacao"    TEXT,
        PRIMARY KEY("codtarefa" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "tarefaturma" (
        "codtarefaturma"        INTEGER NOT NULL,
        "codtarefa"     INTEGER NOT NULL,
        "codturma"      INTEGER NOT NULL,
        "datainicio"    INTEGER,
        "datafim"       INTEGER,
        "observacao"    INTEGER,
        FOREIGN KEY("codturma") REFERENCES "turma"("codturma"),
        FOREIGN KEY("codtarefa") REFERENCES "tarefa"("codtarefa"),
        PRIMARY KEY("codtarefaturma" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "tarefaturmaaluno" (
        "codtarefaturmaaluno"   INTEGER NOT NULL,
        "codtarefaturma"        INTEGER NOT NULL,
        "codaluno"      INTEGER NOT NULL,
        "dataentrega"   TEXT,
        "entregas"      INTEGER DEFAULT 0,
        "resultados"    TEXT,
        "nota"  INTEGER,
        "observacao"    TEXT,
        PRIMARY KEY("codtarefaturmaaluno" AUTOINCREMENT),
        FOREIGN KEY("codaluno") REFERENCES "aluno"("codaluno"),
        FOREIGN KEY("codtarefaturma") REFERENCES "tarefaturma"("codtarefaturma")
);

CREATE TABLE IF NOT EXISTS "tipousuario" (
        "codtipousuario"        INTEGER,
        "descricao"     TEXT,
        PRIMARY KEY("codtipousuario" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "turma" (
        "codturma"      INTEGER,
        "codcurso"      INTEGER NOT NULL,
        "descricao"     TEXT,
        "sigla" TEXT,
        "observacao"    TEXT,
        FOREIGN KEY("codcurso") REFERENCES curso(codcurso),
        PRIMARY KEY("codturma" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "turmaaluno" (
        "codturmaaluno" INTEGER NOT NULL,
        "codturma"      INTEGER NOT NULL,
        "codaluno"      INTEGER NOT NULL,
        PRIMARY KEY("codturmaaluno" AUTOINCREMENT),
        FOREIGN KEY("codturma") REFERENCES "turma"("codturma"),
        UNIQUE("codturma","codaluno"),
        FOREIGN KEY("codaluno") REFERENCES "aluno"("codaluno")
);