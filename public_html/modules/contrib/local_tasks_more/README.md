# Local Tasks More

The **Local Tasks More** module adds a show more/less tasks toggle to primary or secondary local tasks. 

By default, the show more/less tasks toggle is only applied 
to nodes (i.e., entity.node.canonical), and the node 'Delete' is hidden.

## Use Case

Nodes (a.k.a. Content) in Drupal can have too many local tasks.  
Only the first few (i.e., View, Edit, Layout, Revisions, and Translate) are 
used on a daily basis. This module hides less commonly used local tasks
(i.e, Usage, Clone, Devel, Convert) and allows the local task title 
and weight to be altered or remove a local task from display.


## Features

- Adds more/less local task toggle to select local task base routes.

- Allows a local task's title and weight to altered.

- Allows a local task to be removed.

- Removes the contextual links from the local tasks block.


## Notes

- Generally, only nodes have more than 7 local tasks.

- This module's approach is compatible with any front-end or admin
  theme that renders local tasks based of Drupal core's default templates
  (menu-local-tasks.html.twig and menu-local-task.html.twig).

- This module does not rely entirely on JavaScript but instead adds
  the needed Show more/less task, icon, and classes via the PHP using 
  `hook_menu_local_tasks_alter()` and `hook_preprocess_menu_local_task()`,
  which helps ensures the toggle works as expected.

- The show more/less icon is an inline SVG whose color can be easily changed 
  via CSS. (i.e, `.local-tasks-more-toggle svg path {fill: blue;}`)


## Similar Modules

- [Admin Local Tasks](https://www.drupal.org/project/admin_local_tasks)  
  Make Drupal (Admin) Local Task links fancier and more accessible for content
  editors on non-admin routes (front pages) - with minimalistic design 
  and fixed position on left or right side.

- [Admin Toolbar Tasks](https://www.drupal.org/project/admin_toolbar_tasks)  
  Split of and display administrative local tasks as part of the site Toolbar.

- [Better Local Tasks](https://www.drupal.org/project/betterlt)  
  Adds a bit of polish to the local task tabs, by fixed positioning it
  and adding some icons, and hover animation.

- [Sticky Local Tasks](https://www.drupal.org/project/sticky_local_tasks)  
  Provides more user-friendly, better, and fancier sticky local tasks.

- [Tidy Local Tasks](https://www.drupal.org/project/tidy_local_tasks)  
  Creates a tidy editorial experience by hiding Drupal's local tasks.

