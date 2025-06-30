import time
import threading
from random import random

fila_de_pedidos = []
pedidos_prontos = []
num_cozinheiros = 5
num_pedidos = 100
cozinheiros = []


def cozinheiro(num_cozinheiro):
    print(f"cozinheiro {num_cozinheiro} pronto para receber pedidos!")
    while len(fila_de_pedidos) != 0:
        pedido = fila_de_pedidos[0]
        print(f"cozinheiro {num_cozinheiro} preparando {pedido=}")
        time.sleep(random())
        print(f"cozinheiro {num_cozinheiro} terminou {pedido=}")
        pedidos_prontos.append(pedido)
        fila_de_pedidos.remove(pedido)

def faz_pedido(pedido):
    fila_de_pedidos.append(pedido)
    print(f"{pedido=} recebido...")

for pedido in range(num_pedidos):
    faz_pedido(pedido)

for c in range(num_cozinheiros):
    t = threading.Thread(target=cozinheiro, args=(c, ))
    t.start()
    cozinheiros.append(t)

inicio = time.time()
for thread in cozinheiros:
    thread.join()
fim = time.time()
