# Pendencias do template AMP

Arquivo analisado: `/home/leo/Projetos/Web/Htdocs/eupenso.com/config/amp.html`

## O que ainda precisa ser corrigido

1. Garantir que `[alternative_link]` sempre gere um `<link rel="canonical" href="...">`.
2. Confirmar que `[css_root]` gera exatamente um bloco `<style amp-custom>` valido.
3. Verificar se o CSS total da pagina AMP fica dentro do limite atual de 75.000 bytes.
4. Revisar o bloco `[structured]` para garantir que o JSON-LD gerado seja valido.
5. Revisar `[amp_script]` para incluir apenas componentes AMP realmente usados na pagina.
6. Revisar o bloco `<amp-analytics>` porque, se `[analytics]` ainda usar propriedade `UA-...`, isso esta legado.
7. Migrar o analytics AMP para GA4 quando for fazer a atualizacao de medicao.
8. Atualizar os links de compartilhamento com as redes sociais, revisando formatos de URL, parametros e redes ainda suportadas.
9. Validar uma pagina AMP gerada no validador oficial: `https://playground.amp.dev/validator`

## Referencias

- AMP HTML spec: `https://amp.dev/documentation/guides-and-tutorials/learn/spec/amphtml/`
- AMP boilerplate: `https://amp.dev/documentation/guides-and-tutorials/learn/spec/amp-boilerplate/`
- amp-analytics: `https://amp.dev/documentation/components/amp-analytics/`
- amp-ad: `https://amp.dev/documentation/components/amp-ad/`
- Style and layout: `https://amp.dev/documentation/guides-and-tutorials/develop/style_and_layout/`
- Google Analytics 4: `https://support.google.com/analytics/answer/10089681?hl=en`
