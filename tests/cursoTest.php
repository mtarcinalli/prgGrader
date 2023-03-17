<?php
#declare(strict_types=1);
require_once '../src/modules/obj2db/src/obj2db.php';
require_once '../src/conectdb.php';

require_once '../src/classes/acessocurso.php';
require_once '../src/classes/curso.php';
require_once '../src/classes/tipousuario.php';
require_once '../src/classes/turma.php';
require_once '../src/classes/usuario.php';


use PHPUnit\Framework\TestCase;

final class cursoTest extends TestCase
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
    * @covers \Curso
    */
    public function testCurso(): void
    {
        global $db;
        $codProprietario = 1;

        # inserção
        $curso1 = new Curso();
        $curso1->setDescricao("Curso 01");
        $curso1->setSigla("CT 01");
        $curso1->setObservacao("Obs");
        $curso1->proprietario($codProprietario);
        $curso1->salvar();
        $curso2 = new Curso();
        $curso2->carregar($curso1->getCodCurso());
        $this->assertSame($curso1->getDescricao(), $curso2->getDescricao());
        $this->assertSame($curso1->getSigla(), $curso2->getSigla());
        $this->assertSame($curso1->getObservacao(), $curso2->getObservacao());

		$cmd = "SELECT * FROM curso WHERE codcurso = :codcurso";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codcurso', $curso1->getCodCurso(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($curso1->getDescricao(), $row['descricao']);
        $this->assertSame($curso1->getSigla(), $row['sigla']);
        $this->assertSame($curso1->getObservacao(), $row['observacao']);

		$cmd = "SELECT * FROM acessocurso WHERE codcurso = :codcurso";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codcurso', $curso1->getCodCurso(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($curso1->getCodCurso(), $row['codcurso']);
        $this->assertSame($codProprietario, $row['codusuario']);
        $this->assertSame($curso1->listaAcessos()[0], 1);

        # alteração
        $curso1->setDescricao("Curso 001");
        $curso1->setSigla("CT 001");
        $curso1->setObservacao("Observação");
        $curso1->salvar();
		$cmd = "SELECT * FROM curso WHERE codcurso = :codcurso";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codcurso', $curso1->getCodCurso(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($curso1->getDescricao(), $row['descricao']);
        $this->assertSame($curso1->getSigla(), $row['sigla']);
        $this->assertSame($curso1->getObservacao(), $row['observacao']);

        # exclusão após inserção de turma
        $turma = new Turma();
        $turma->setCurso($curso1);
        $turma->setDescricao("Turma 01");
        $turma->setSigla("T01");
        $turma->setObservacao("Obs 01");
        $turma->salvar();
        try {
            $curso1->excluir();
        } catch (Exception $e) {
        }
		$cmd = "SELECT * FROM curso WHERE codcurso = :codcurso";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codcurso', $curso2->getCodCurso(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($e->getCode(), 23503);
        $this->assertSame($curso1->getSigla(), $row['sigla']);
        $this->assertSame($curso1->getObservacao(), $row['observacao']);

        # eclusão após exclusão de turma
        $turma->excluir();
        $curso1->excluir();
		$cmd = "SELECT * FROM curso WHERE codcurso = :codcurso";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codcurso', $curso2->getCodCurso(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row, false);


    }



}