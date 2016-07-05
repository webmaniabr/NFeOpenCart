# Nota Fiscal Eletrônica para OpenCart

Através do emissor de Nota Fiscal da WebmaniaBR®, você conta com a emissão e arquivamento das suas notas fiscais, cálculo automático de impostos, geração do Danfe para impressão e envio automático de e-mails para os clientes. Instale o módulo grátis do OpenCart e contrate um dos planos de Nota Fiscal Eletrônica da WebmaniaBR® a partir de R$29,90/mês: <a href="https://webmaniabr.com/smartsales/nota-fiscal-eletronica/" target="_blank">Assine Agora</a>.

- **Módulo compatível com as versões 2.0 e 2.2**
- Sobre o Emissor de NF-e da WebmaniaBR®: <a href="https://webmaniabr.com/smartsales/nota-fiscal-eletronica/" target="_blank">Saiba mais</a>
- Documentação da REST API: <a href="https://webmaniabr.com/docs/rest-api-nfe/" target="_blank">Visualizar</a>

## Requisitos

- Contrate um dos planos de Nota Fiscal Eletrônica da WebmaniaBR® a partir de R$29,90/mês: <a href="https://webmaniabr.com/smartsales/nota-fiscal-eletronica/" target="_blank">Assine Agora</a>
- Faça o download da última versão do módulo: <a href="https://github.com/webmaniabr/NFeOpenCart/releases" target="_blank">Clique aqui</a>
- Instale o módulo grátis do OpenCart da WebmaniaBR® e configure conforme instruções.

## Instalação do Módulo

O módulo de OpenCart da WebmaniaBR® é compatível com as versões 2.0 e 2.2. Para isso é necessário transferir todos os arquivos que estão dentro da pasta, de acordo com a versão da sua loja virtual. Por exemplo, caso a sua loja virtual seja a versão 2.2, envie todos os arquivos na raiz da sua loja que estão dentro da pasta 2.2.

```
Pasta 2.0 = Compatível com a versão OpenCart 2.0
Pasta 2.2 = Compatível com a versão OpenCart 2.2
```

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

O módulo utiliza vQmod para inserir os campos obrigatórios na página de Finalizar Compra, Cadastro de novo cliente e Painel de controle para que as notas fiscais sejam emitidas corretamente. É importante que o tema da loja virtual siga o checkout padrão para que possa inserir os campos necessários, porém alterações podem ser feitas no arquivo /admin/controle/nfe/xml/nfe.ocmod.xml e /vqmod/xml/nfe.ocmod.xml.

Havendo dúvidas de como manusear o arquivo e eventuais incompatibilidades com o seu tema, por favor, entre em contato no e-mail suporte@webmaniabr.com.

## Instruções

**OpenCart 2.0 ou superior**

- <a href="https://webmaniabr.com/atendimento/nfe/opencart/configuracao-modulo-nf-e-opencart-20/" target="_blank">Configuração do Módulo NF-e</a>
- <a href="https://webmaniabr.com/atendimento/nfe/opencart/emissao-de-nfe-opencart-20/" target="_blank">Emissão de NF-e</a>
- <a href="https://webmaniabr.com/atendimento/nfe/opencart/cadastro-de-ncm-opencart-20/" target="_blank">Cadastro de NCM</a>

## Controle das Notas Fiscais

Você pode gerenciar todas as Notas Fiscais e realizar a impressão do Danfe no painel da WebmaniaBR®. Simples e fácil.

<p align="center">
<img src="https://webmaniabr.com/wp-content/themes/wmbr/img/nf07.jpg">
</p>

## Suporte

Qualquer dúvida entre em contato na nossa <a href="https://webmaniabr.com/atendimento/" target="_blank">Central de Atendimento</a> ou no e-mail suporte@webmaniabr.com.
