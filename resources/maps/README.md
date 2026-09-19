# Mapa do dashboard

Fonte: IBGE, API de Malhas Geográficas v3, malha simplificada do Brasil com divisões por UF.

- Documentação: https://servicodados.ibge.gov.br/api/docs/malhas?versao=3
- Origem: https://servicodados.ibge.gov.br/api/v3/malhas/paises/BR?formato=application/vnd.geo%2Bjson&qualidade=minima&intrarregiao=UF
- Obtido em 19/09/2026.
- `brazil-states.json`: 27 caminhos SVG, um por UF, derivados da resposta GeoJSON pública. Coordenadas projetadas em Mercator, ajustadas proporcionalmente ao viewBox `0 0 640 640` e arredondadas a duas casas decimais. Não é um mapa para navegação ou delimitação legal.
- As malhas são incorporadas ao HTML no servidor. Nenhuma chamada ao IBGE, CDN ou serviço de mapas ocorre ao abrir o dashboard.
- Os códigos e regiões das UFs estão em `app/Support/BrazilStates.php`.
