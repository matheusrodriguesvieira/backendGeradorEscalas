<?php
if ($api == 'equipamentos') {
    if ($metodo == 'GET') {

        if ($acao == 'index' && $parametro == '') {
            $db = DB::connect();
            $sql = $db->prepare("SELECT * FROM equipamentos");
            $sql->execute();
            $obj = $sql->fetchAll(PDO::FETCH_ASSOC);

            if ($obj) {
                echo json_encode($obj);
            } else {
                $response = array(
                    "message" => "Nenhum equipamento encontrado!"
                );
                echo json_encode($response);
            }

            exit;
        }

        if ($acao == 'show' && $parametro != '') {
            $db = DB::connect();
            $sql = $db->prepare("SELECT * FROM equipamentos WHERE equipamentos.tag = '{$parametro}'");
            $sql->execute();
            $obj = $sql->fetchObject();

            if ($obj) {
                echo json_encode($obj);
            } else {
                $response = array(
                    "message" => "Nenhum equipamento encontrado!"
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
                $sql = $db->prepare("SELECT * FROM equipamentos WHERE equipamentos.tag = '{$parametro}'");
                $sql->execute();
                $obj = $sql->fetchObject();

                if (!$obj) {
                    echo json_encode(["message" => "Não foi possível encontrar o equipamento"]);
                    exit;
                }

                $comando = "UPDATE equipamentos SET ";

                $json = file_get_contents("php://input");
                $dados = json_decode($json, true);

                $contador = 1;

                foreach (array_keys($dados) as $key) {
                    if (count($dados) > $contador) {
                        $comando .= "$key = '{$dados[$key]}', ";
                    } else {
                        $comando .= "$key = '{$dados[$key]}' ";
                    }

                    $contador++;
                }

                $comando .= "WHERE tag = '$parametro'";

                // echo $comando;
                $sql = $db->prepare($comando);

                try {
                    $response = $sql->execute();
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
