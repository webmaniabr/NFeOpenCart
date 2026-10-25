<p align="center">
  <img src="docs/assets/webmania-logo.png" width="396" alt="Webmania">
</p>

# Nota Fiscal Eletrônica para OpenCart

Através do emissor de Nota Fiscal da Webmania®, você conta com a emissão e arquivamento das suas notas fiscais, cálculo automático de impostos, geração do Danfe para impressão e envio automático de e-mails para os clientes.

- **Distribuições para OpenCart 2.0, 2.1, 2.2, 2.3, 3.0 e 4.x; consulte os ambientes verificados abaixo.**
- **Faça download da última versão do módulo: [Clique aqui](https://github.com/webmaniabr/NFeOpenCart/releases)**
- Emissor de Nota Fiscal Webmania®: [Saiba mais](https://webmaniabr.com/nota-fiscal-eletronica/)
- Documentação da REST API: [Visualizar](https://webmaniabr.com/docs/rest-api-nfe/)

## Compatibilidade verificada

| OpenCart | Pasta / pacote | PHP verificado |
| --- | --- | --- |
| 2.0.3.1 | `2.0` / `opencart-2.0.zip` | 5.6 |
| 2.1.0.2 | `2.1` / `opencart-2.1.zip` | 5.6 |
| 2.2.0.0 | `2.2` / `opencart-2.2.zip` | 5.6 |
| 2.3.0.2 | `2.3` / `opencart-2.3.zip` | 5.6 |
| 3.0.5.1 | `3.0` / `opencart-3.0.zip` | 7.4 |
| 4.0.2.3 e 4.1.0.4 | `4.X` / `webmaniabrnfe.ocmod.zip` | 8.3 |

O código da distribuição `4.X` também passou nas verificações adicionais em PHP 8.5. As distribuições antigas e a distribuição 4.X têm pacotes e versões internas próprios.

A tabela registra os ambientes exercitados. Use o PHP exigido pela versão da sua plataforma e valide as integrações da loja em homologação antes de atualizar.

## Requisitos

- Contrate um dos planos de Nota Fiscal Eletrônica da Webmania®: [Assine Agora](https://webmaniabr.com/nota-fiscal-eletronica/)
- Instale o módulo do OpenCart da Webmania® e configure conforme instruções.

## Instalação

Baixe o [release mais recente](https://github.com/webmaniabr/NFeOpenCart/releases/latest) e selecione o pacote da sua versão.

**OpenCart 2.x e 3.x:** descompacte `opencart-<versão>.zip` e envie seu conteúdo para a raiz da loja, preservando as pastas `admin/`, `catalog/` e `vqmod/`. Se o diretório administrativo foi renomeado, envie o conteúdo de `admin/` para o diretório utilizado pela sua loja. Instale o vQmod e habilite o módulo conforme a seção de ativação abaixo.

**OpenCart 4.x:** envie `webmaniabrnfe.ocmod.zip` em **Extensões > Instalador**, instale o pacote e habilite **Webmania NF-e** em **Extensões > Extensões > Módulos**. Esse pacote contém `install.json` na raiz e usa os eventos nativos do OpenCart 4; não utiliza o vQmod das distribuições antigas.

## Ativação no OpenCart 2.x e 3.x

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

## Adaptar página Finalizar Compra no OpenCart 2.x e 3.x

O módulo utiliza vQmod para inserir os campos obrigatórios na página de Finalizar Compra, Cadastro de novo cliente e Painel de controle para que as notas fiscais sejam emitidas corretamente. É importante que o tema da loja virtual siga o checkout padrão para que possa inserir os campos necessários, porém alterações podem ser feitas nos seguintes arquivos

- /admin/controller/nfe/xml/nfe.ocmod.xml
- /vqmod/xml/nfe.ocmod.xml

Havendo dúvidas de como manusear o arquivo e eventuais incompatibilidades com o seu tema, por favor, entre em contato no e-mail suporte@webmaniabr.com.

## Controle das Notas Fiscais

Você pode gerenciar todas as Notas Fiscais e realizar a impressão do Danfe no painel da Webmania®. Simples e fácil.

<p align="center">
<img src="https://wmbr.s3.amazonaws.com/img/dashboard_webmaniabr_01.jpg">
</p>

## Atualizações desta versão

- Aceita CNPJ numérico e alfanumérico nos formulários e na integração.
- Reforça permissões administrativas, vínculo dos documentos ao cliente e proteção das credenciais.
- Preserva os campos fiscais omitidos em atualizações parciais e o funcionamento das edições antigas.
- Inclui a distribuição própria para OpenCart 4 e atualiza a geração de PDFs.

## Suporte

Qualquer dúvida acesse o [Painel Webmania®](https://webmaniabr.com/painel/) para *abrir um chamado* ou conversar em tempo real no *chat*.
