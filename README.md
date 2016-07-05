# Nota Fiscal Eletrônica para OpenCart

Através do emissor de Nota Fiscal da WebmaniaBR®, você conta com a emissão e arquivamento das suas notas fiscais, cálculo automático de impostos, geração do Danfe para impressão e envio automático de e-mails para os clientes.

- **Módulo compatível com as versões 2.0 e 2.2**
- **Faça download da última versão do módulo: [Clique aqui](https://github.com/webmaniabr/NFeOpenCart/releases)**
- Sobre o Emissor de NF-e da WebmaniaBR®: [Saiba mais](https://webmaniabr.com/smartsales/nota-fiscal-eletronica/)
- Documentação da REST API: [Visualizar](https://webmaniabr.com/docs/rest-api-nfe/)

## Requisitos

- Contrate um dos planos de Nota Fiscal Eletrônica da WebmaniaBR® a partir de R$29,90/mês: [Assine Agora](https://webmaniabr.com/smartsales/nota-fiscal-eletronica/)
- Instale o módulo grátis do OpenCart da WebmaniaBR® e configure conforme instruções.

## Instalação do Módulo

Após realizar o download da última versão ([Clique aqui](https://github.com/webmaniabr/NFeOpenCart/releases)), descompacte o arquivo zip e envie todos os arquivos na pasta raiz da sua loja virtual. A transferência pode ser realizada através do acesso FTP da sua hospedagem.

## Ativação do Módulo

- Acesse o menu Extensões > Módulos. Localize o módulo chamado **WebmaniaBR NF-e** e clique no botão Instalar.

<p align="center">
<img src="https://webmaniabr.com/painel/wp-content/uploads/sites/2/2016/06/1467039339.png">
</p>

- Com o módulo ativado, acesse o seguinte link para instalar o vQmod (obrigatório para o funcionamento correto do módulo de NF-e): http://www.sualojavirtual.com.br/vqmod/install/.

```
Explicação técnica: O vQmod é um inovador sistema de substituição de arquivos através de um método conhecido como “virtual”. Na prática, ao invés de modificar os arquivos originais em PHP do OpenCart as instruções são alteradas em arquivos XML.
```

## Adaptar página Finalizar Compra

O módulo utiliza vQmod para inserir os campos obrigatórios na página de Finalizar Compra, Cadastro de novo cliente e Painel de controle para que as notas fiscais sejam emitidas corretamente. É importante que o tema da loja virtual siga o checkout padrão para que possa inserir os campos necessários, porém alterações podem ser feitas nos seguintes arquivos

- /admin/controller/nfe/xml/nfe.ocmod.xml
- /vqmod/xml/nfe.ocmod.xml

Havendo dúvidas de como manusear o arquivo e eventuais incompatibilidades com o seu tema, por favor, entre em contato no e-mail suporte@webmaniabr.com.

## Instruções

**OpenCart 2.0 ou superior**

- [Configuração do Módulo NF-e](https://webmaniabr.com/atendimento/nfe/opencart/configuracao-modulo-nf-e-opencart-20/)
- [Emissão de NF-e](https://webmaniabr.com/atendimento/nfe/opencart/emissao-de-nfe-opencart-20/)
- [Cadastro de NCM](https://webmaniabr.com/atendimento/nfe/opencart/cadastro-de-ncm-opencart-20/)

## Controle das Notas Fiscais

Você pode gerenciar todas as Notas Fiscais e realizar a impressão do Danfe no painel da WebmaniaBR®. Simples e fácil.

<p align="center">
<img src="https://webmaniabr.com/wp-content/themes/wmbr/img/nf07.jpg">
</p>

## Suporte

Qualquer dúvida entre em contato na nossa <a href="https://webmaniabr.com/atendimento/" target="_blank">Central de Atendimento</a> ou no e-mail suporte@webmaniabr.com.
