// eslint-disable-next-line import/no-unresolved
import { Command } from 'ckeditor5/src/core';
import { createEmbeddedContent } from './utils';

export default class InsertEmbeddedContentCommand extends Command {
  execute(attributes, element) {
    const { model } = this.editor;
    const embeddedContentEditing = this.editor.plugins.get(
      'embeddedContentEditing',
    );

    const dataAttributeMapping = Object.fromEntries(
      Object.entries(embeddedContentEditing.attrs).map(([key, value]) => [
        value,
        key,
      ]),
    );

    // \Drupal\embedded_content\Form\EmbeddedContentDialog returns data in keyed by
    // data-attributes used in view data. This converts data-attribute keys to
    // keys used in model.
    const modelAttributes = Object.fromEntries(
      Object.keys(dataAttributeMapping)
        .filter((attribute) => attributes[attribute])
        .map((attribute) => [
          dataAttributeMapping[attribute],
          attributes[attribute],
        ]),
    );

    model.change((writer) => {
      model.insertContent(
        createEmbeddedContent(writer, modelAttributes, element),
      );
    });
  }
}
