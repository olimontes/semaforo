<?php

$num_cozinheiros = 5;
$num_pedidos = 100;

// Cria chave e memória compartilhada
$shm_key = ftok(__FILE__, 'P');
$shm_id = shm_attach($shm_key, 2048, 0666);

// Cria chave e semáforo SysV
$sem_key = ftok(__FILE__, 'S');
$sem_id = sem_get($sem_key, 1);

// Inicializa a fila de pedidos
$fila_de_pedidos = range(0, $num_pedidos - 1);
shm_put_var($shm_id, 1, $fila_de_pedidos);

// Inicializa o array de pedidos feitos por cada cozinheiro
$pedidos_por_cozinheiro = array_fill(0, $num_cozinheiros, []);
shm_put_var($shm_id, 2, $pedidos_por_cozinheiro);

// Função do processo cozinheiro
function cozinheiro($id, $shm_id, $sem_id)
{
    echo "Cozinheiro {$id} pronto para receber pedidos!\n";

    while (true) {
        sem_acquire($sem_id);

        // Verifica se ainda há a fila
        if (!shm_has_var($shm_id, 1)) {
            sem_release($sem_id);
            break;
        }

        $fila = shm_get_var($shm_id, 1);

        if (empty($fila)) {
            shm_remove_var($shm_id, 1);
            sem_release($sem_id);
            break;
        }

        $pedido = array_shift($fila);
        shm_put_var($shm_id, 1, $fila);

        // Atualiza a lista de pedidos por cozinheiro
        $pedidos_por_cozinheiro = shm_get_var($shm_id, 2);
        $pedidos_por_cozinheiro[$id][] = $pedido;
        shm_put_var($shm_id, 2, $pedidos_por_cozinheiro);

        sem_release($sem_id);

        echo "Cozinheiro {$id} preparando pedido={$pedido}\n";
        usleep(rand(100000, 500000));
        echo "Cozinheiro {$id} terminou pedido={$pedido}\n";
    }

    exit(0);
}

// Tempo inicial
$inicio = microtime(true);

// Cria os processos
$pids = [];
for ($i = 0; $i < $num_cozinheiros; $i++) {
    $pid = pcntl_fork();
    if ($pid === -1) {
        die("Erro ao criar processo\n");
    } elseif ($pid === 0) {
        cozinheiro($i, $shm_id, $sem_id);
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

// Exibe pedidos por cozinheiro
$pedidos_por_cozinheiro = shm_get_var($shm_id, 2);
foreach ($pedidos_por_cozinheiro as $cozinheiro => $pedidos) {
    echo "Cozinheiro {$cozinheiro} atendeu os pedidos: " . implode(', ', $pedidos) . "\n";
}
// Libera recursos
shm_remove($shm_id);
sem_remove($sem_id);

