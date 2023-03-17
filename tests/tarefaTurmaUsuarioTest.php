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
    public function testCorrigir(): void
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
    }
}
