# Inventario de esquema WordPress

Lectura autenticada y sin modificaciones. No incluye contenido editorial ni credenciales. Los nombres internos proceden del esquema activo; no identifican por sí solos al plugin que los registra.

```json
{
  "generatedAt": "2026-10-18T14:58:22.176Z",
  "endpoint": "https://content.alexcuadra.dev/graphql",
  "contentTypes": [
    {
      "graphqlPluralName": "posts",
      "graphqlSingleName": "post",
      "hasArchive": null,
      "label": "Posts",
      "name": "post",
      "restBase": null,
      "showInRest": null,
      "showUi": null
    },
    {
      "graphqlPluralName": "pages",
      "graphqlSingleName": "page",
      "hasArchive": null,
      "label": "Pages",
      "name": "page",
      "restBase": null,
      "showInRest": null,
      "showUi": null
    },
    {
      "graphqlPluralName": "mediaItems",
      "graphqlSingleName": "mediaItem",
      "hasArchive": null,
      "label": "Media",
      "name": "attachment",
      "restBase": null,
      "showInRest": null,
      "showUi": null
    },
    {
      "graphqlPluralName": "projects",
      "graphqlSingleName": "project",
      "hasArchive": null,
      "label": "Projects",
      "name": "project",
      "restBase": null,
      "showInRest": null,
      "showUi": null
    },
    {
      "graphqlPluralName": "articles",
      "graphqlSingleName": "article",
      "hasArchive": null,
      "label": "Articles",
      "name": "articles",
      "restBase": null,
      "showInRest": null,
      "showUi": null
    }
  ],
  "contentTypesTruncated": false,
  "frontendRootFields": [
    {
      "name": "articles",
      "type": {
        "kind": "OBJECT",
        "name": "RootQueryToArticleConnection",
        "ofType": null
      }
    },
    {
      "name": "categories",
      "type": {
        "kind": "OBJECT",
        "name": "RootQueryToCategoryConnection",
        "ofType": null
      }
    },
    {
      "name": "pages",
      "type": {
        "kind": "OBJECT",
        "name": "RootQueryToPageConnection",
        "ofType": null
      }
    },
    {
      "name": "projects",
      "type": {
        "kind": "OBJECT",
        "name": "RootQueryToProjectConnection",
        "ofType": null
      }
    }
  ]
}
```

Pendiente inspeccionar el plugin custom, grupos ACF exportados y configuración de registro para decidir si basta conservar las claves post_type al pasar el registro a ACF. No desactivar ni desinstalar el plugin.
