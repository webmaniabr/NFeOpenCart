<p align="center">
  <img src="https://wmbr.s3.amazonaws.com/img/logo_webmaniabr_github2.png">
</p>

# Nota Fiscal Eletrônica para OpenCart

Através do emissor de Nota Fiscal da Webmania®, você conta com a emissão e arquivamento das suas notas fiscais, cálculo automático de impostos, geração do Danfe para impressão e envio automático de e-mails para os clientes.

- **Módulo compatível com as versões 2.0, 2.2, 2.3 e 3.0.**
- **Faça download da última versão do módulo: [Clique aqui](https://github.com/webmaniabr/NFeOpenCart/releases)**
- Emissor de Nota Fiscal Webmania®: [Saiba mais](https://webmaniabr.com/smartsales/nota-fiscal-eletronica/)
- Documentação da REST API: [Visualizar](https://webmaniabr.com/docs/rest-api-nfe/)

## Requisitos

- Contrate um dos planos de Nota Fiscal Eletrônica da Webmania®: [Assine Agora](https://webmaniabr.com/smartsales/nota-fiscal-eletronica/)
- Instale o módulo grátis do OpenCart da Webmania® e configure conforme instruções.

## Instalação

Após realizar o download da última versão ([Clique aqui](https://github.com/webmaniabr/NFeOpenCart/releases)), descompacte o arquivo zip e envie todos os arquivos na pasta raiz da sua loja virtual. A transferência pode ser realizada através do acesso FTP da sua hospedagem.

## Ativação

- Acesse o menu Extensões > Módulos. Localize o módulo chamado **WebmaniaBR NF-e** e clique no botão Instalar.

<p align="center">
<img src="https://webmaniabr.com/painel/wp-content/uploads/sites/2/2016/06/1467039339.png">
</p>

- Com o módulo ativado, acesse o seguinte link para instalar o vQmod (obrigatório para o funcionamento correto do módulo de NF-e): http://www.sualojavirtual.com.br/vqmod/install/.

```
Explicação técnica: O vQmod é um inovador sistema de substituição de arquivos através de um método conhecido como “virtual”. Na prática, ao invés de modificar os arquivos originais em PHP do OpenCart as instruções são alteradas em arquivos XML.
```

## Configuração

Consulte o nosso guia passo a passo para começar a emitir as notas fiscais com apenas um clique na sua Loja Virtual:

- [Configurar credenciais](https://ajuda.webmaniabr.com/hc/pt-br/articles/360013113812-Configurar-credenciais-no-OpenCart)
- [Configurar impostos](https://ajuda.webmaniabr.com/hc/pt-br/articles/360013122032-Configurar-impostos-no-OpenCart)
- [Emitir Nota Fiscal](https://ajuda.webmaniabr.com/hc/pt-br/articles/360013352631-Emiss%C3%A3o-de-NF-e-no-OpenCart)

## Adaptar página Finalizar Compra

O módulo utiliza vQmod para inserir os campos obrigatórios na página de Finalizar Compra, Cadastro de novo cliente e Painel de controle para que as notas fiscais sejam emitidas corretamente. É importante que o tema da loja virtual siga o checkout padrão para que possa inserir os campos necessários, porém alterações podem ser feitas nos seguintes arquivos

- /admin/controller/nfe/xml/nfe.ocmod.xml
- /vqmod/xml/nfe.ocmod.xml

Havendo dúvidas de como manusear o arquivo e eventuais incompatibilidades com o seu tema, por favor, entre em contato no e-mail suporte@webmaniabr.com.

## Controle das Notas Fiscais

Você pode gerenciar todas as Notas Fiscais e realizar a impressão do Danfe no painel da Webmania®. Simples e fácil.

<p align="center">
<img src="https://wmbr.s3.amazonaws.com/img/dashboard_webmaniabr_01.jpg">
</p>

## Suporte

Qualquer dúvida acesse o [Painel Webmania®](https://webmaniabr.com/painel/) para *abrir um chamado* ou conversar em tempo real no *chat*.
