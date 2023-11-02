<?php
if ($api == 'operadores') {
    if ($metodo == 'GET') {
        if ($acao == 'index' && $parametro == '') {

            // ---------------------------------------
            // PEGA TODOS OS EQUIPAMENTOS
            // ---------------------------------------

            $json = file_get_contents("php://input");
            $dados = json_decode($json, true);

            if (!$dados) {
                exit;
            }

            if (!array_key_exists('turma', $dados)) {
                $response = array(
                    "message" => 'Parâmetro \'turma\' não encontrado.'
                );
                echo json_encode($response);
                exit;
            }

            $db = DB::connect();
            $sql = $db->prepare("SELECT * FROM operadores WHERE operadores.turma = ?");
            $sql->execute([$dados['turma']]);
            $obj = $sql->fetchAll(PDO::FETCH_ASSOC);

            if (!$obj) {
                $response = array(
                    "message" => "Nenhum operador encontrado!"
                );
                echo json_encode($response);
                exit;
            }

            echo json_encode($obj);
            exit;
        }

        if ($acao == 'show' && $parametro != '') {
            // ---------------------------------------
            // PEGA UM EQUIPAMENTO ESPECÍFICO
            // ---------------------------------------


            $db = DB::connect();
            $sql = $db->prepare("SELECT * FROM operadores WHERE operadores.matricula = {$parametro}");
            $sql->execute();
            $obj = $sql->fetchObject();

            if (!$obj) {
                $response = array(
                    "message" => "Nenhum operador encontrado!"
                );
                echo json_encode($response);
            }

            echo json_encode($obj);
            exit;
        }
    }

    if ($metodo == 'PUT') {
        if ($acao == 'update') {
            if ($parametro != "") {

                // 1- VERIFICAR SE EXISTE O DADO PARA MODIFICAR;
                // 2- MODIFICAR 
                // 3 - RETORNAR A MENSAGEM DE ERRO OU SUCESSOR

                $db = DB::connect();
                $sql = $db->prepare("SELECT * FROM operadores WHERE operadores.matricula = '{$parametro}'");
                $sql->execute();
                $obj = $sql->fetchObject();

                if (!$obj) {
                    echo json_encode(["message" => "Não foi possível encontrar o operador"]);
                    exit;
                }


                $json = file_get_contents("php://input");
                $dados = json_decode($json, true);

                $sql = "UPDATE operadores SET ";

                $contador = 1;
                foreach (array_keys($dados) as $key) {
                    if (count($dados) > $contador) {
                        $sql .= "$key = '{$dados[$key]}', ";
                    } else {
                        $sql .= "$key = '{$dados[$key]}' ";
                    }

                    $contador++;
                }

                $sql .= "WHERE operadores.matricula = '$parametro'";

                // echo $sql;
                $exec = $db->prepare($sql);

                try {
                    $response = $exec->execute();
                    echo json_encode(["message" => "Dados atualizados com sucesso!"]);
                } catch (Exception $e) {
                    echo json_encode([
                        "message" => "Erro ao atualizar os dados!",
                        "error" => $e->getMessage(),
                    ]);
                }

                exit;
            }
        }
    }
}
