/* eslint-disable import/no-extraneous-dependencies, import/no-unresolved */
import { Plugin } from 'ckeditor5/src/core';
import EntityEmbedEditing from './editing';
import EntityEmbedToolbar from './toolbar';
import EntityEmbedUI from './ui';
import EntityEmbedGeneralHtmlSupport from './generalhtmlsupport';
import EntityEmbedLink from './link';

export default class EntityEmbed extends Plugin {
  static get requires() {
    return [
      EntityEmbedEditing,
      EntityEmbedUI,
      EntityEmbedToolbar,
      EntityEmbedGeneralHtmlSupport,
      EntityEmbedLink,
    ];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'EntityEmbed';
  }
}
