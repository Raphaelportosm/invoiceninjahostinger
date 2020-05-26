context('Recurring invoices', () => {
    beforeEach(() => {
        cy.clientLogin();
    });

    // test url

    it('should show recurring invoices page', () => {
        cy.visit('/client/recurring_invoices');

        cy.location().should(location => {
            expect(location.pathname).to.eq('/client/recurring_invoices');
        });
    });

    it('should show reucrring invoices text', () => {
        cy.visit('/client/recurring_invoices');

        cy.get('h3')
            .first()
            .should('contain.text', 'Recurring Invoices');
    });

    it('should have per page options dropdown', () => {
        cy.visit('/client/recurring_invoices');

        cy.get('body')
            .find('select')
            .first()
            .should('have.value', '10');
    });

    it('should have required table elements', () => {
        cy.visit('/client/recurring_invoices');

        cy.get('body')
            .find('table.recurring-invoices-table > tbody > tr')
            .first()
            .find('a')
            .first()
            .should('contain.text', 'View')
            .click()
            .location()
            .should(location => {
                expect(location.pathname).to.eq('/client/recurring_invoices/VolejRejNm');
            });
    });
});
