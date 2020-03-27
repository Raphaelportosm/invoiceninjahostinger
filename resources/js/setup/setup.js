/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

import Axios from "axios";

class Setup {
    constructor() {
        this.checkDbButton = document.getElementById("test-db-connection");
        this.checkDbAlert = document.getElementById("database-response");
        this.checkDbEndpoint = document.querySelector(
            'meta[name="test-db-endpoint"]'
        ).content;

        this.checkSmtpButton = document.getElementById("test-smtp-connection");
        this.checkSmtpAlert = document.getElementById("smtp-response");
        this.checkSmtpEndpoint = document.querySelector(
            'meta[name="test-smtp-endpoint"]'
        ).content;
    }

    handleDatabaseCheck() {

        let data = {
            host: document.querySelector('input[name="host"]').value,
            database: document.querySelector('input[name="database"]').value,
            username: document.querySelector('input[name="db_username"]').value,
            password: document.querySelector('input[name="db_password"]').value,
        }

        Axios.post(this.checkDbEndpoint, data)
            .then(response => this.handleSuccess(this.checkDbAlert))
            .catch(e => this.handleFailure(this.checkDbAlert));
    }

    handleSmtpCheck() {

        let data = {
            driver: document.querySelector('select[name="mail_driver"]').value,
            from_name: document.querySelector('input[name="mail_name"]').value,
            from_address: document.querySelector('input[name="mail_address"]').value,
            username: document.querySelector('input[name="mail_username"]').value,
            host: document.querySelector('input[name="mail_host"]').value,
            port: document.querySelector('input[name="mail_port"]').value,
            encryption: document.querySelector('select[name="encryption"]').value,
            password: document.querySelector('input[name="mail_password"]').value,
        }

        Axios.post(this.checkSmtpEndpoint, data)
            .then(response => this.handleSuccess(this.checkSmtpAlert))
            .catch(e => this.handleFailure(this.checkSmtpAlert));
    }

    handleSuccess(element) {
        element.classList.remove("alert-failure");
        element.innerText = "Success!";
        element.classList.add("alert-success");
    }

    handleFailure(element) {
        element.classList.remove("alert-success");
        element.innerText = "Oops, looks like something isn't correct!";
        element.classList.add("alert-failure");
    }

    handle() {
        this.checkDbButton.addEventListener("click", () =>
            this.handleDatabaseCheck()
        );

        this.checkSmtpButton.addEventListener("click", () =>
            this.handleSmtpCheck()
        );
    }
}

new Setup().handle();
