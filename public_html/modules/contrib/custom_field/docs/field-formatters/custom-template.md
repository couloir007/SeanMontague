# Custom template formatter
The **Custom template** (`custom_template`) formatter displays the items using a
custom template with token replacement. This formatter is most useful where 
advanced HTML output is needed to leverage the token replacement system.
Available tokens are listed for replacement options.

| Setting  | Label    | Description                                       | Default |
|----------|----------|---------------------------------------------------|---------|
| tokens   | Tokens   | The token types available (`basic` or `advanced`) | basic   |
| template | Template | The text value of the HTML + tokens               |         |

> [!tip] Advanced tokens
> While not a dependency, the [Token](https://www.drupal.org/project/token){target="_blank"}
> module is highly recommended and required to use `advanced` **Tokens**
> setting.
>
> For Drupal versions >= 11.3, we recommend the
> [Token Browser](https://www.drupal.org/project/token_browser){target="_blank"}
> as an alternative Token browser element for performance and unlimited depth.
