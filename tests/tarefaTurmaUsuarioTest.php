<?php
#declare(strict_types=1);
require_once '../src/modules/obj2db/src/obj2db.php';
require_once '../src/conectdb.php';

require_once '../src/classes/acessocurso.php';
require_once '../src/classes/curso.php';
require_once '../src/classes/corretor.php';
require_once '../src/classes/tipousuario.php';
require_once '../src/classes/tarefa.php';
require_once '../src/classes/tarefaturma.php';
require_once '../src/classes/tarefaturmausuario.php';
require_once '../src/classes/turma.php';
require_once '../src/classes/turmausuario.php';
require_once '../src/classes/usuario.php';


use PHPUnit\Framework\TestCase;

final class tarefaTurmaUsuarioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $db;
        $cmd = "DELETE FROM tarefaturmausuario";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

        $cmd = "DELETE FROM turmausuario";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

        $cmd = "DELETE FROM usuario WHERE codusuario > 1";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

        $cmd = "DELETE FROM tarefaturma";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

        $cmd = "DELETE FROM turma";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

		$cmd = "DELETE FROM acessocurso";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

        $cmd = "DELETE FROM curso";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

    }


    /**
    * @covers \TarefaTurmaTest::carregar
    */
    public function testCarregar(): void
    {
        $tarefaTurmaUsuario = new TarefaTurmaUsuario();

        $this->assertSame($tarefaTurmaUsuario->carregar(12334664, 123212332), false);
    }

    /**
    * @covers \TarefaTurmaTest::corrigir
    */
    public function testCorrigirCpp(): void
    {
        global $db;
        $codProprietario = 1;

        $curso = new Curso();
        $curso->setDescricao("Curso 01");
        $curso->proprietario($codProprietario);
        $curso->salvar();
        $codcurso = $curso->getCodCurso();

        $turma = new Turma();
        $turma->setCurso($curso);
        $turma->setDescricao("Turma 01");
        $turma->salvar();


        $tipoAluno = new TipoUsuario(4);

        $aluno = new Usuario();
        $aluno->setNome("aluno 01");
        $aluno->setEmail("aluno01@usp.br");
        $aluno->setTipoUsuario($tipoAluno);
        $aluno->setAlterasenha(false);
        $aluno->salvar();

        $alunosTurma = new TurmaUsuario();
        $alunosTurma->setTurma($turma);
        $alunosTurma->setUsuario($aluno);
        $alunosTurma->salvar();

        $corretor = new Corretor(1);

        $tarefa = new Tarefa();
        $tarefa->setCorretor($corretor);
        $tarefa->setDescricao("T01");
        $tarefa->salvar();
        $tarefaDir = "../uploads/TAREFAS/T" . $tarefa->getCodTarefa();
        mkdir($tarefaDir);
        $arquivo = "../tests/files/cxxtest/solution.zip";
        copy($arquivo, "$tarefaDir/solution.zip");
        $cmd = "unzip $tarefaDir/solution.zip -d $tarefaDir/solution/";
        $output = shell_exec($cmd);


        $tarefaTurma = new TarefaTurma();
        $tarefaTurma->setTarefa($tarefa);
        $tarefaTurma->setTurma($turma);
        $tarefaTurma->setDataInicio('2000-12-15');
        $tarefaTurma->setDataFim('2050-12-15');
        $tarefaTurma->salvar();

        $arquivo = "../tests/files/cxxtest/solution60.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";

        $tarefaTurmaUsuario = new TarefaTurmaUsuario();
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);

        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 1);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 60);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);

		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 1);
        $this->assertSame($row['nota'], 60);
        $this->assertSame($row['notafinal'], null);


        $arquivo = "../tests/files/cxxtest/solution60.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";

        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 2);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 60);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 2);
        $this->assertSame($row['nota'], 60);
        $this->assertSame($row['notafinal'], null);

        $arquivo = "../tests/files/cxxtest/solution80.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 3);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 80);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 3);
        $this->assertSame($row['nota'], 80);
        $this->assertSame($row['notafinal'], null);

        $arquivo = "../tests/files/cxxtest/solution0loop.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 4);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 0);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 4);
        $this->assertSame($row['nota'], 0);
        $this->assertSame($row['notafinal'], null);

        $arquivo = "../tests/files/cxxtest/solution0compilation.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 5);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 0);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 5);
        $this->assertSame($row['nota'], 0);
        $this->assertSame($row['notafinal'], null);

        $arquivo = "../tests/files/cxxtest/solution100.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 6);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 100);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 6);
        $this->assertSame($row['nota'], 100);
        $this->assertSame($row['notafinal'], null);

    }

    /**
    * @covers \TarefaTurmaTest::corrigir
    */
    public function testCorrigirJunit(): void
    {
        global $db;
        $codProprietario = 1;

        $curso = new Curso();
        $curso->setDescricao("Curso 01");
        $curso->proprietario($codProprietario);
        $curso->salvar();
        $codcurso = $curso->getCodCurso();

        $turma = new Turma();
        $turma->setCurso($curso);
        $turma->setDescricao("Turma 01");
        $turma->salvar();

        $tipoAluno = new TipoUsuario(4);

        $aluno = new Usuario();
        $aluno->setNome("aluno 01");
        $aluno->setEmail("aluno01@usp.br");
        $aluno->setTipoUsuario($tipoAluno);
        $aluno->setAlterasenha(false);
        $aluno->salvar();

        $alunosTurma = new TurmaUsuario();
        $alunosTurma->setTurma($turma);
        $alunosTurma->setUsuario($aluno);
        $alunosTurma->salvar();

        $corretor = new Corretor(2);

        $tarefa = new Tarefa();
        $tarefa->setCorretor($corretor);
        $tarefa->setDescricao("T01");
        $tarefa->salvar();
        $tarefaDir = "../uploads/TAREFAS/T" . $tarefa->getCodTarefa();
        mkdir($tarefaDir);
        $arquivo = "../tests/files/junit/solution.zip";
        copy($arquivo, "$tarefaDir/solution.zip");
        $cmd = "unzip $tarefaDir/solution.zip -d $tarefaDir/solution/";
        $output = shell_exec($cmd);


        $tarefaTurma = new TarefaTurma();
        $tarefaTurma->setTarefa($tarefa);
        $tarefaTurma->setTurma($turma);
        $tarefaTurma->setDataInicio('2000-12-15');
        $tarefaTurma->setDataFim('2050-12-15');
        $tarefaTurma->salvar();

        $arquivo = "../tests/files/junit/Calculator0erro.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";

        $tarefaTurmaUsuario = new TarefaTurmaUsuario();
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);

        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 1);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 0);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);

		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 1);
        $this->assertSame($row['nota'], 0);
        $this->assertSame($row['notafinal'], null);


        $arquivo = "../tests/files/junit/Calculator50.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";

        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 2);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 50);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 2);
        $this->assertSame($row['nota'], 50);
        $this->assertSame($row['notafinal'], null);

        $arquivo = "../tests/files/junit/Calculator75.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 3);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 75);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 3);
        $this->assertSame($row['nota'], 75);
        $this->assertSame($row['notafinal'], null);

        $arquivo = "../tests/files/junit/Calculator0loop.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 4);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 75);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 4);
        $this->assertSame($row['nota'], 75);
        $this->assertSame($row['notafinal'], null);

        $arquivo = "../tests/files/junit/Calculator0.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 5);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 0);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 5);
        $this->assertSame($row['nota'], 0);
        $this->assertSame($row['notafinal'], null);

        $arquivo = "../tests/files/junit/Calculator100.zip";
        copy($arquivo, "../tests/files/temp.zip");
        $arquivo = "../tests/files/temp.zip";
        $tarefaTurmaUsuario->corrigir($tarefaTurma, $aluno, $arquivo);
        $this->assertSame($tarefaTurmaUsuario->getDataEntrega(), date("Y-m-d"));
        $this->assertSame($tarefaTurmaUsuario->getEntregas(), 6);
        $this->assertSame($tarefaTurmaUsuario->getNota(), 100);
        $this->assertSame($tarefaTurmaUsuario->getNotafinal(), null);
		$cmd = "SELECT * FROM tarefaturmausuario WHERE codtarefaturmausuario = :codtarefaturmausuario";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codtarefaturmausuario', $tarefaTurmaUsuario->getCodTarefaTurmaUsuario(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row['dataentrega'], date("Y-m-d"));
        $this->assertSame($row['entregas'], 6);
        $this->assertSame($row['nota'], 100);
        $this->assertSame($row['notafinal'], null);
    }
}
