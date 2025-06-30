
## 🟢 O que é um **semáforo**?

Um **semáforo** em programação é como um **sinal de trânsito** para controlar o acesso de vários processos a um recurso **compartilhado**, como uma fila ou uma variável.

Imagine que:

- Todos os cozinheiros querem acessar **a mesma fila de pedidos** ao mesmo tempo.
- Sem controle, dois cozinheiros podem pegar **o mesmo pedido**, ou até causar erros ao mexer na fila ao mesmo tempo.

➡️ **O semáforo evita isso**, garantindo que **só um cozinheiro por vez** possa acessar a fila.

---

## 🧩 Como funciona no seu código

### Passo a passo do uso:

1. **Criamos o semáforo**:

   ```php
   $sem_id = sem_get($sem_key, 1);
   ```

   Isso cria (ou recupera) um semáforo que será usado por todos os processos.

2. **O cozinheiro tenta acessar a fila**:
   Antes de acessar a fila de pedidos, ele **trava o semáforo**:

   ```php
   sem_acquire($sem_id); // LOCK
   ```

   Isso **bloqueia os outros cozinheiros** de mexerem na fila até que ele termine.

3. **Faz as operações na fila**:

   - Verifica se a fila existe
   - Pega um pedido
   - Atualiza os pedidos que ele já atendeu

4. **Libera o semáforo**:
   Depois de terminar, ele libera o acesso para outro cozinheiro:

   ```php
   sem_release($sem_id); // UNLOCK
   ```

---

## 🧠 Analogia simples

Pense em uma **cozinha com 5 cozinheiros** e **1 única tábua de cortar carne** (a fila de pedidos).

- Cada cozinheiro precisa esperar sua vez de usar a tábua.
- O **semáforo é como uma chave da cozinha**. Só quem estiver com a chave pode usar a tábua.
- Quando termina, ele **devolve a chave** (libera o semáforo) e outro cozinheiro pega.

---

## 🔐 Por que é importante?

Sem o semáforo:

- Dois cozinheiros poderiam pegar o mesmo pedido ao mesmo tempo.
- Um deles pode sobrescrever dados do outro.
- A fila poderia ficar corrompida ou com pedidos perdidos.

---

## ✅ Resumo

| Ação                    | Função usada    | O que faz                              |
| ----------------------- | --------------- | -------------------------------------- |
| Criar/obter semáforo    | `sem_get()`     | Cria um semáforo para todos usarem     |
| Entrar na seção crítica | `sem_acquire()` | Trava o semáforo para acesso exclusivo |
| Sair da seção crítica   | `sem_release()` | Libera o semáforo para outro processo  |
