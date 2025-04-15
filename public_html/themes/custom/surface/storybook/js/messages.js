/* eslint-disable */
/**
 * @file
 * Message template overrides to solve Drupal 10.3 issue:
 * https://www.drupal.org/project/uswds_base/issues/3465493
 */

((Drupal) => {
  /**
   * Overrides message theme function.
   *
   * @param {object} message
   *   The message object.
   * @param {string} message.text
   *   The message text.
   * @param {object} options
   *   The message context.
   * @param {string} options.type
   *   The message type.
   * @param {string} options.id
   *   ID of the message, for reference.
   *
   * @return {HTMLElement}
   *   A DOM Node.
   */
  Drupal.theme.message = ({ text }, { type, id }) => {
    const messagesTypes = Drupal.Message.getMessageTypeLabels();
    const messageWrapper = document.createElement('div');

    messageWrapper.setAttribute(
      'class',
      `messages--${type === 'status' ? 'success' : type} messages--${type} messages-list__item`,

    );
    messageWrapper.setAttribute(
      'role',
      type === 'error' || type === 'warning' ? 'alert' : 'status',
    );
    messageWrapper.setAttribute('aria-labelledby', `${id}-title`);
    messageWrapper.setAttribute('data-drupal-message-id', id);
    messageWrapper.setAttribute('data-drupal-message-type', type);

    messageWrapper.innerHTML = `
    <div class="messages__container" data-drupal-selector="messages-container">
      <div class="messages__header">
        <h2 id="${id}-title" class="visually-hidden messages__title">${messagesTypes[type]}</h2>
      </div>
      <div class="messages__content">
        ${text}
      </div>
    </div>
  `;

    return messageWrapper;
  };
})(Drupal);
