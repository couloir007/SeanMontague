describe('Mercury Editor e2e tests.', () => {

  before(() => {
    // Install the Mercury Editor test module.
    cy.drush('en mercury_editor_setup_test');
    // Clear the cache.
    cy.drush('cr');
    // Give us a taller viewport to work with.
    cy.viewport(1000, 800);
  });

  beforeEach(() => {
    // Login as admin.
    cy.loginUserByUid(1);
  });

  it('creates, edits, and deletes a node with Mercury Editor', () => {
    // Create a new page.
    cy.visit('/node/add/me_test_ct');
    cy.get('#edit-title-0-value').clear().type('-- Test page --');
    // Tests that syncing the title field works.
    cy.get('input[name="title[0][value]"]').clear().type('-- Test page --');
    cy.iframe('#me-preview').find('.page-title').contains(' -- Test page --');
    cy.basicMercuryEditorInteractions();
    cy.meDeletePage();
  });

  it('creates multiple revisions of a node with Mercury Editor', () => {
    // Enable the mercury_editor_content_moderation_test module.
    cy.drush('en mercury_editor_content_moderation_test');
    // Create a new page.
    cy.visit('/node/add/me_test_ct');
    cy.get('[name="revision_log[0][value]"]').type('First draft.');
    cy.meSavePage();
    cy.meExitEditor().then((url) => {
      const nid = url.match(/node\/(\d+)/)[1];
      cy.get('.tabs__link').contains('Revisions').click();
      cy.get('.revision-current').contains('First draft.');
      cy.get('.tabs__link').contains('View').click();
      cy.meEditPage();
      cy.meExitEditor();
      cy.meEditPage();
      cy.get('[name="moderation_state[0][state]"]').select('Published');
      cy.get('[name="revision_log[0][value]"]').type('Published draft.');
      cy.meSavePage();
      cy.meExitEditor();
      cy.get('.tabs__link').contains('Revisions').click();
      cy.get('.revision-current').contains('Published draft.');

      cy.get('.tabs__link').contains('View').click();
      cy.meEditPage();
      cy.get('[name="moderation_state[0][state]"]').select('Draft');
      cy.get('[name="revision_log[0][value]"]').type('New unpublished draft.');
      cy.meSavePage();
      cy.meExitEditor();
      cy.get('.tabs__link').contains('Revisions').click();
      cy.get('.node-revision-table tr:first-child').contains('New unpublished draft.');

      cy.get('.tabs__link').contains('View').click();
      cy.meEditPage();
      cy.get('[name="moderation_state[0][state]"]').select('Published');
      cy.get('[name="revision_log[0][value]"]').type('Latest published draft.');
      cy.meSavePage();
      cy.meExitEditor();
      cy.get('.tabs__link').contains('Revisions').click();
      cy.get('.revision-current').contains('Latest published draft.');

      // Change moderation state without exiting the editor.
      cy.get('.tabs__link').contains('View').click();
      cy.meEditPage();
      cy.get('[name="moderation_state[0][state]"]').select('Draft');
      cy.get('[name="revision_log[0][value]"]').type('Latest draft - without exit.');
      cy.meSavePage();
      cy.get('[name="moderation_state[0][state]"]').select('Published');
      cy.get('[name="revision_log[0][value]"]').clear();
      cy.get('[name="revision_log[0][value]"]').type('Latest published - without exit.');
      cy.meSavePage();
      cy.meExitEditor();

      cy.get('.tabs__link').contains('Revisions').click();
      cy.get('body').find('.revision-current').should('contain', 'Latest published - without exit.');

    });
  });

  it('creates, edits, and deletes a taxonomy term with Mercury Editor', () => {
    // Enable the mercury_editor_content_moderation_test module.
    cy.drush('en mercury_editor_taxonomy_term_test');

    // Create a new term.
    cy.visit('admin/structure/taxonomy/manage/me_test_vocab/add');
    // Tests that syncing the title field works.
    cy.get('input[name="name[0][value]"]').clear().type('-- Test term --');
    cy.iframe('#me-preview').find('.page-title').contains(' -- Test term --');

    cy.basicMercuryEditorInteractions();
    cy.meDeletePage();
  });

  it('tests layout builder compatibility', () => {
    // Install the Mercury Editor test module.
    cy.drush('en mercury_editor_layout_builder_test');
    // Create a new page.
    cy.visit('/node/add/me_test_ct');
    cy.get('#edit-title-0-value').clear().type('-- Test page --');
    // Tests that syncing the title field works.
    cy.get('input[name="title[0][value]"]').clear().type('-- Test page --');
    cy.iframe('#me-preview').find('.page-title').contains(' -- Test page --');
    cy.basicMercuryEditorInteractions();
    cy.meDeletePage();
  });

  it('tests that block conditional visibility works with Mercury Editor', () => {
    cy.drush('en mercury_editor_block_visibility_test')
    cy.visit('/node/add/me_test_ct')
    cy.iframe('#me-preview').find('.block-mercury-editor-block-visibility-test').should('contain', 'Tests block visibility with Mercury Editor.')
    cy.meAddComponent('me_test_section')
      cy.meChooseLayout('layout_twocol')
      cy.meSaveComponent().then((section) => {
        cy.meAddComponent('me_test_text', {
          region: 'first',
          section
        })
        cy.meSetCKEditor5Value('field_me_test_text', 'Left');
        cy.meSaveComponent().then((component) => {
          cy.iframe('#me-preview').find(component).should('contain', 'Left');
        })

        cy.meAddComponent('me_test_text', {
          region: 'second',
          section
        })
        cy.meSetCKEditor5Value('field_me_test_text', 'Right')
        cy.meSaveComponent().then((component) => {
          cy.iframe('#me-preview').find(component).should('contain', 'Right')
        })
      })

      cy.meSavePage()
      cy.iframe('#me-preview').find('.block-mercury-editor-block-visibility-test').should('contain', 'Tests block visibility with Mercury Editor.')
      cy.meExitEditor()
      cy.get('.block-mercury-editor-block-visibility-test').should('contain', 'Tests block visibility with Mercury Editor.')
      cy.meDeletePage()
      cy.drush('pmu mercury_editor_block_visibility_test')
  });

  it('tests field validation with Mercury Editor component', () => {
    // Install the test module.
    cy.drush('en mercury_editor_field_validation_test');
    // Creates a new 2-column section and attempts to save it without enterring a value for the "label" field.
    cy.visit('/node/add/me_test_ct');
    cy.meAddComponent('me_test_section');
    cy.meChooseLayout('layout_twocol');
    // Save the section without entering a value for the "label" field, wich
    // will trigger a validation error.
    cy.meSaveComponent().then((invalid_field) => {
      // The "invalid_field" form field should be visible and have an error class.
      cy.get(invalid_field)
        .should('be.visible')
        .and('have.class', 'error');
    });
    // Uninstall the test module.
    cy.drush('cr');
    cy.drush('pmu mercury_editor_field_validation_test');
  });

});
