<?php
if ($api == 'operadores') {
    if ($metodo == 'GET') {
        if ($acao == 'index' && $parametro == '') {
            $db = DB::connect();
            $sql = $db->prepare("SELECT * FROM operadores");
            $sql->execute();
            $obj = $sql->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($obj);
            exit;
        }

        if ($acao == 'show' && $parametro != '') {
            $db = DB::connect();
            $sql = $db->prepare("SELECT * FROM operadores WHERE operadores.matricula = {$parametro}");
            $sql->execute();
            $obj = $sql->fetchObject();

            if ($obj) {
                echo json_encode($obj);
            } else {
                $response = array(
                    "message" => "Nenhum operador encontrado!"
                );
                echo json_encode($response);
            }
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
