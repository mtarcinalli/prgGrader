<?php require_once 'header.php'; ?>
<form action="relnotas.php" method="post" role="form" class="form-horizontal">
<div class="form-group">
    <div class="col-sm-10">
<select name="codturma" id="codturma" class="form-control">
<option>[Turma]</option>
<?php
$cmd = "SELECT codturma, c.descricao as curso , t.descricao as turma ".
        "FROM turma t " .
        "INNER JOIN curso c ON t.codcurso = c.codcurso ".
        "ORDER BY c.descricao, t.descricao";
$tbl = $db->prepare($cmd);
$tbl->execute();
while ($row = $tbl->fetch()){
    echo "<option value='$row[codturma]' ";
    if (isset($_REQUEST['codturma']))
        if ($row['codturma'] == $_REQUEST['codturma'])
            echo " selected";
    echo ">$row[curso] - $row[turma]</option>";
}
?>
</select>
</div>
<div class="col-sm-2">
    				<button type="submit" class="btn btn-primary">Filtrar</button>
			</div>
</div>
</form>
<?php
if (isset($_REQUEST["codturma"])) {
    # tarefas
    $cmd = "select tt.codtarefaturma, t.sigla ".
            "FROM tarefaturma tt ".
            "INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
            "WHERE tt.codturma = :codturma ".
            "ORDER BY t.sigla ASC";
    $tblTarefas = $db->prepare($cmd);
    $tblTarefas->bindValue(':codturma', $_REQUEST['codturma'], PDO::PARAM_INT);
    $tblTarefas->execute();
    $tarefas = array();
    $avaliacoes = array();
    while ($row = $tblTarefas->fetch(PDO::FETCH_ASSOC)) {
        if ($row["sigla"] == "AVALIAÇÃO-SUB") continue;
        $tarefas[] = $row;
        if (substr($row["sigla"], 0, 6) === "AVALIA") {
            $avaliacoes[] = $row["codtarefaturma"];
        }
    }
    # alunos
    $cmd = "SELECT a.codaluno, a.nome, a.email, codturma ".
                "FROM aluno a ".
                "INNER JOIN turmaaluno ta ON a.codaluno = ta.codaluno ".
                "WHERE codturma = :codturma ORDER BY a.nome";
    $tblAlunos = $db->prepare($cmd);
    $tblAlunos->bindValue(':codturma', $_REQUEST['codturma'], PDO::PARAM_INT);
    $tblAlunos->execute();
    while ($row = $tblAlunos->fetch(PDO::FETCH_ASSOC)) {
        $alunos[] = $row;
    }
    # notas
    $cmd = "SELECT tta.codtarefaturma, codaluno, notafinal, codturma ".
                "FROM tarefaturmaaluno tta ".
                "INNER JOIN tarefaturma tt ON tta.codtarefaturma = tt.codtarefaturma ".
                "WHERE codturma = :codturma ".
                "";
    $tblAlunos = $db->prepare($cmd);
    $tblAlunos->bindValue(':codturma', $_REQUEST['codturma'], PDO::PARAM_INT);
    $tblAlunos->execute();
    $notas = array();
    while ($row = $tblAlunos->fetch(PDO::FETCH_ASSOC)) {
        $notas[$row["codaluno"]][$row["codtarefaturma"]] = $row["notafinal"];
    }
    # exibição
    echo "<table class=\"table\">".
            "<tr>".
            "<th>Aluno</th>".
            "<th>E-mail</th>";
    foreach ($tarefas as $tarefa) {
        echo "<th>$tarefa[sigla]</th>";
    }
    echo "<th>FINAL</th></tr>";
    foreach ($alunos as $aluno) {
        echo "<tr>";
        echo "<td>$aluno[nome]</td>";
        echo "<td>$aluno[email]</td>";
        $notasAv = 0;
        $notasTrab = 0;
        foreach ($tarefas as $tarefa) {
            if (array_key_exists($aluno["codaluno"], $notas)) {
                echo "<td class=\"text-center\">" . $notas[$aluno["codaluno"]][$tarefa["codtarefaturma"]] . "</td>\n";
                if (in_array($tarefa["codtarefaturma"], $avaliacoes)) {
                    $notasAv += $notas[$aluno["codaluno"]][$tarefa["codtarefaturma"]];
                } else {
                    $notasTrab += $notas[$aluno["codaluno"]][$tarefa["codtarefaturma"]];
                }
            } else {
                echo "<td class=\"text-center\"></td>\n";
            }
        }
        $notaFinal = ceil(  ($notasAv / 2 * 0.7) + ($notasTrab / 7 * 0.3));
        if ($aluno["codturma"] == 9 || $aluno["codturma"] == 8) {
            $notaFinal = ceil(  ($notasAv / 2 * 0.7) + ($notasTrab / 6 * 0.3));
        }
        echo "<td class=\"text-center\">$notaFinal</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
    </div>
</body>
</html>