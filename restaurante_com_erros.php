<?php

$num_cozinheiros = 5;
$num_pedidos = 100;

// Cria chave e memória compartilhada
$shm_key = ftok(__FILE__, 'P');
$shm_id = shm_attach($shm_key, 2048, 0666);

// ❌ PROBLEMA 1: NÃO CRIAMOS SEMÁFORO PARA CONTROLE DE CONCORRÊNCIA
// Sem semáforo, múltiplos processos podem acessar a memória compartilhada simultaneamente
// Isso causa RACE CONDITIONS - quando dois processos acessam o mesmo recurso ao mesmo tempo

// Inicializa a fila de pedidos
$fila_de_pedidos = range(0, $num_pedidos - 1);
shm_put_var($shm_id, 1, $fila_de_pedidos);

// Inicializa o array de pedidos feitos por cada cozinheiro
$pedidos_por_cozinheiro = array_fill(0, $num_cozinheiros, []);
shm_put_var($shm_id, 2, $pedidos_por_cozinheiro);

// Função do processo cozinheiro - VERSÃO COM ERROS
function cozinheiro($id, $shm_id)
{
    echo "Cozinheiro {$id} pronto para receber pedidos!\n";

    while (true) {
        // ❌ PROBLEMA 2: SEM PROTEÇÃO DA REGIÃO CRÍTICA
        // Múltiplos cozinheiros podem executar estas linhas simultaneamente
        
        // Verifica se ainda há a fila
        if (!shm_has_var($shm_id, 1)) {
            echo "Cozinheiro {$id}: Fila não existe mais\n";
            break;
        }

        $fila = shm_get_var($shm_id, 1);
        
        // ❌ PROBLEMA 3: CONDIÇÃO DE CORRIDA CRÍTICA
        // Entre as linhas abaixo, outro processo pode modificar a fila
        // Cenário: Cozinheiro A lê fila com 1 pedido, Cozinheiro B também lê a mesma fila
        // Ambos veem o mesmo pedido e vão tentar processá-lo
        
        if (empty($fila)) {
            echo "Cozinheiro {$id}: Fila vazia, parando\n";
            shm_remove_var($shm_id, 1);
            break;
        }

        // ❌ PROBLEMA 4: LEITURA E MODIFICAÇÃO NÃO ATÔMICA
        // Entre array_shift() e shm_put_var(), outro processo pode interferir
        echo "Cozinheiro {$id}: Fila atual tem " . count($fila) . " pedidos\n";
        
        $pedido = array_shift($fila);
        echo "Cozinheiro {$id}: Peguei pedido {$pedido}\n";
        
        // ❌ PROBLEMA 5: DELAY INTENCIONAL PARA FORÇAR RACE CONDITION
        // Este sleep força a situação onde múltiplos processos estão na região crítica
        usleep(10000); // 10ms de delay - tempo suficiente para outro processo interferir
        
        shm_put_var($shm_id, 1, $fila);

        // ❌ PROBLEMA 6: ATUALIZAÇÃO DO CONTADOR TAMBÉM SEM PROTEÇÃO
        // Dois cozinheiros podem ler o mesmo array, modificar e sobrescrever um ao outro
        $pedidos_por_cozinheiro = shm_get_var($shm_id, 2);
        $pedidos_por_cozinheiro[$id][] = $pedido;
        
        // Mais um delay para aumentar chances de race condition
        usleep(5000);
        
        shm_put_var($shm_id, 2, $pedidos_por_cozinheiro);

        echo "Cozinheiro {$id} preparando pedido={$pedido}\n";
        
        // ❌ PROBLEMA 7: SIMULAÇÃO DE TEMPO DE PREPARO
        // Durante este tempo, outros cozinheiros continuam acessando a fila
        usleep(rand(100000, 500000));
        echo "Cozinheiro {$id} terminou pedido={$pedido}\n";
    }

    exit(0);
}

echo "=== INICIANDO VERSÃO COM ERROS DE CONCORRÊNCIA ===\n";
echo "⚠️  ATENÇÃO: Este código demonstra problemas de race condition\n";
echo "Você pode observar pedidos duplicados, perdidos ou inconsistências\n\n";

// Tempo inicial
$inicio = microtime(true);

// Cria os processos
$pids = [];
for ($i = 0; $i < $num_cozinheiros; $i++) {
    $pid = pcntl_fork();
    if ($pid === -1) {
        die("Erro ao criar processo\n");
    } elseif ($pid === 0) {
        // ❌ PROBLEMA 8: PASSA APENAS $shm_id, SEM SEMÁFORO
        cozinheiro($i, $shm_id);
    } else {
        $pids[] = $pid;
    }
}

// Espera todos terminarem
foreach ($pids as $pid) {
    pcntl_waitpid($pid, $status);
}

// Tempo final
$fim = microtime(true);
$tempo_total = $fim - $inicio;
echo "\nTempo total: {$tempo_total} segundos\n";

// ❌ PROBLEMA 9: VERIFICAÇÃO FINAL PODE MOSTRAR INCONSISTÊNCIAS
echo "\n=== RESULTADOS (PROVAVELMENTE COM ERROS) ===\n";

// Tenta ler os pedidos por cozinheiro
if (shm_has_var($shm_id, 2)) {
    $pedidos_por_cozinheiro = shm_get_var($shm_id, 2);
    $todos_pedidos = [];
    $total_pedidos_processados = 0;

    foreach ($pedidos_por_cozinheiro as $cozinheiro => $pedidos) {
        echo "Cozinheiro {$cozinheiro} atendeu os pedidos: " . implode(', ', $pedidos) . "\n";
        $todos_pedidos = array_merge($todos_pedidos, $pedidos);
        $total_pedidos_processados += count($pedidos);
    }

    echo "\n=== ANÁLISE DOS ERROS ENCONTRADOS ===\n";
    echo "Total de pedidos processados: {$total_pedidos_processados}\n";
    echo "Esperado: {$num_pedidos} pedidos\n";

    if ($total_pedidos_processados != $num_pedidos) {
        echo "❌ ERRO: Nem todos os pedidos foram processados!\n";
        if ($total_pedidos_processados < $num_pedidos) {
            echo "   → Pedidos perdidos: " . ($num_pedidos - $total_pedidos_processados) . "\n";
        }
        if ($total_pedidos_processados > $num_pedidos) {
            echo "   → Pedidos duplicados: " . ($total_pedidos_processados - $num_pedidos) . "\n";
        }
    }

    // Verifica duplicatas
    $pedidos_unicos = array_unique($todos_pedidos);
    if (count($pedidos_unicos) != count($todos_pedidos)) {
        $duplicatas_count = count($todos_pedidos) - count($pedidos_unicos);
        echo "❌ ERRO: Foram encontrados {$duplicatas_count} pedidos duplicados!\n";
        
        // Mostra os pedidos duplicados
        $duplicatas = array_diff_assoc($todos_pedidos, $pedidos_unicos);
        echo "   → Pedidos que foram duplicados: " . implode(', ', array_unique($duplicatas)) . "\n";
    }

    // Verifica pedidos perdidos
    sort($todos_pedidos);
    $pedidos_esperados = range(0, $num_pedidos - 1);
    $pedidos_perdidos = array_diff($pedidos_esperados, $todos_pedidos);
    if (!empty($pedidos_perdidos)) {
        echo "❌ ERRO: Pedidos completamente perdidos: " . implode(', ', $pedidos_perdidos) . "\n";
    }

    // Soma matemática
    $soma_esperada = array_sum(range(0, $num_pedidos - 1));
    $soma_obtida = array_sum($todos_pedidos);
    if ($soma_esperada != $soma_obtida) {
        echo "❌ ERRO: Soma matemática incorreta!\n";
        echo "   → Esperada: {$soma_esperada}, Obtida: {$soma_obtida}\n";
        echo "   → Diferença: " . ($soma_obtida - $soma_esperada) . "\n";
    }

} else {
    echo "❌ ERRO CRÍTICO: Não foi possível ler os dados finais!\n";
    echo "   → Provavelmente houve corrupção na memória compartilhada\n";
}

// Verifica se ainda há pedidos na fila
if (shm_has_var($shm_id, 1)) {
    $fila_restante = shm_get_var($shm_id, 1);
    if (!empty($fila_restante)) {
        echo "❌ ERRO: Restaram " . count($fila_restante) . " pedidos na fila!\n";
        echo "   → Pedidos não processados: " . implode(', ', $fila_restante) . "\n";
    }
}

echo "\n=== EXPLICAÇÃO DOS PROBLEMAS ===\n";
echo "Os erros acima ocorreram devido à ausência de controle de concorrência:\n\n";
echo "1. RACE CONDITION: Múltiplos processos acessam a mesma memória simultaneamente\n";
echo "2. OPERAÇÕES NÃO ATÔMICAS: Leitura e escrita não são protegidas\n";
echo "3. INCONSISTÊNCIA DE DADOS: Estado da fila fica corrompido\n";
echo "4. PEDIDOS DUPLICADOS: Dois cozinheiros pegam o mesmo pedido\n";
echo "5. PEDIDOS PERDIDOS: Atualizações são sobrescritas\n\n";
echo "SOLUÇÃO: Usar semáforos para proteger a região crítica!\n";

// Libera recursos
try {
    shm_remove($shm_id);
} catch (Exception $e) {
    echo "Erro ao limpar memória compartilhada: " . $e->getMessage() . "\n";
}

?>
