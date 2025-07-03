<?php

echo "=== TESTE DE VALIDAÇÃO - EXECUTANDO 10 VEZES ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

$total_execucoes = 10;
$sucessos = 0;
$falhas = 0;
$resultados = [];

for ($i = 1; $i <= $total_execucoes; $i++) {
    echo "Execução $i de $total_execucoes...\n";
    
    // Executa o programa e captura a saída
    ob_start();
    $output = shell_exec('php restaurante_semaforo.php 2>&1');
    ob_end_clean();
    
    if ($output === null) {
        echo "  ✗ Erro ao executar o programa\n";
        $falhas++;
        continue;
    }
    
    // Verifica se todos os critérios foram atendidos
    $todos_processados = strpos($output, '✓ Todos os pedidos foram processados') !== false ? 1 : 0;
    $sem_duplicatas = strpos($output, '✓ Nenhum pedido foi duplicado') !== false ? 1 : 0;
    $sem_perdidos = strpos($output, '✓ Nenhum pedido foi perdido') !== false ? 1 : 0;
    $fila_vazia = strpos($output, '✓ A fila de pedidos terminou vazia') !== false ? 1 : 0;
    $soma_correta = strpos($output, '✓ Soma matemática correta') !== false ? 1 : 0;
    
    $validacoes_ok = $todos_processados + $sem_duplicatas + $sem_perdidos + $fila_vazia + $soma_correta;
    
    $resultado = [
        'execucao' => $i,
        'todos_processados' => $todos_processados,
        'sem_duplicatas' => $sem_duplicatas,
        'sem_perdidos' => $sem_perdidos,
        'fila_vazia' => $fila_vazia,
        'soma_correta' => $soma_correta,
        'validacoes_ok' => $validacoes_ok,
        'sucesso' => $validacoes_ok == 5
    ];
    
    $resultados[] = $resultado;
    
    if ($validacoes_ok == 5) {
        echo "  ✓ Sucesso - Todas as validações passaram\n";
        $sucessos++;
    } else {
        echo "  ✗ Falha - Validações que passaram: $validacoes_ok/5\n";
        $falhas++;
        
        // Mostra quais validações falharam
        if (!$todos_processados) echo "    - Nem todos os pedidos foram processados\n";
        if (!$sem_duplicatas) echo "    - Foram encontrados pedidos duplicados\n";
        if (!$sem_perdidos) echo "    - Alguns pedidos foram perdidos\n";
        if (!$fila_vazia) echo "    - A fila não terminou vazia\n";
        if (!$soma_correta) echo "    - Soma matemática incorreta\n";
    }
    
    // Salva saída detalhada no arquivo de log
    file_put_contents("teste_validacao_detalhado.log", 
        "=== EXECUÇÃO $i ===\n" . $output . "\n\n", 
        FILE_APPEND | LOCK_EX);
        
    sleep(1); // Pequena pausa entre execuções
}

echo "\n=== RESUMO DOS TESTES ===\n";
echo "Total de execuções: $total_execucoes\n";
echo "Sucessos: $sucessos\n";
echo "Falhas: $falhas\n";
echo "Taxa de sucesso: " . round(($sucessos * 100) / $total_execucoes, 1) . "%\n\n";

// Tabela detalhada de resultados
echo "=== RESULTADOS DETALHADOS ===\n";
echo "Exec | Todos | Sem   | Sem   | Fila  | Soma  | Total | Status\n";
echo "     | Proc. | Dupl. | Perd. | Vazia | OK    | OK/5  |\n";
echo "-----+-------+-------+-------+-------+-------+-------+--------\n";

foreach ($resultados as $r) {
    printf("%4d | %5s | %5s | %5s | %5s | %5s | %3d/5 | %s\n",
        $r['execucao'],
        $r['todos_processados'] ? '✓' : '✗',
        $r['sem_duplicatas'] ? '✓' : '✗',
        $r['sem_perdidos'] ? '✓' : '✗',
        $r['fila_vazia'] ? '✓' : '✗',
        $r['soma_correta'] ? '✓' : '✗',
        $r['validacoes_ok'],
        $r['sucesso'] ? 'SUCESSO' : 'FALHA'
    );
}

echo "\n";

if ($falhas == 0) {
    echo "🎉 RESULTADO: Todos os testes passaram! O sistema funciona corretamente.\n";
    echo "O semáforo está protegendo adequadamente a região crítica.\n";
} else {
    echo "⚠️  RESULTADO: Alguns testes falharam. O sistema pode ter problemas de concorrência.\n";
}

echo "\nLog detalhado salvo em: teste_validacao_detalhado.log\n";

?>
