define([
    'Magento_Ui/js/form/element/abstract',
    'ko',
    'underscore'
], function (Abstract, ko, _) {
    return Abstract.extend({
        defaults: {
            elementTmpl: 'Mollie_Subscriptions/product/input/subscription-table',
            rows: ko.observableArray([]),
        },

        initialize: function () {
            this._super();

            this.parseInitialValue();
            this.rows.subscribe(function () { this.updateValue() }.bind(this));

            return this;
        },

        makeId: function() {
            var result           = '';
            var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var charactersLength = characters.length;
            for ( var i = 0; i < 6; i++ ) {
                result += characters.charAt(Math.floor(Math.random() * charactersLength));
            }

            return result;
        },

        parseInitialValue: function () {
            if (typeof this.value() != 'string') {
                this.add();
                return;
            }

            try {
                var json = JSON.parse(this.value());
            } catch (error) {
                this.add();
                return;
            }

            _.each(json, function (row) {
                this.addRow(
                    row.identifier,
                    row.title,
                    row.interval_amount,
                    row.interval_type,
                    row.repetition_amount,
                    row.repetition_type
                );
            }.bind(this));

            if (!this.rows().length) {
                this.add();
            }
        },

        updateValue: function () {
            var rows = this.rows().map( function (row) {
                return {
                    identifier: row.identifier,
                    title: row.title(),
                    interval_amount: row.interval_amount(),
                    interval_type: row.interval_type(),
                    repetition_amount: row.repetition_amount(),
                    repetition_type: row.repetition_type(),
                }
            });

            // Remove empty & non complete rows
            rows = rows.filter( function (row) {
                return row.title &&
                    row.interval_amount &&
                    row.interval_type &&
                    row.repetition_type;
            });

            this.value(JSON.stringify(rows));
        },

        add: function () {
            this.addRow(this.makeId());
        },

        remove: function (row) {
            if (this.rows.length === 1) {
                return;
            }

            this.rows.remove(row);
        },

        addRow: function (identifier, title, interval_amount, interval_type, repetition_amount, repetition_type) {
            var titleObservable = ko.observable(title);
            var intervalAmountObservable = ko.observable(interval_amount);
            var intervalTypeObservable = ko.observable(interval_type);
            var repetitionAmountObservable = ko.observable(repetition_amount);
            var repetitionTypeObservable = ko.observable(repetition_type);

            titleObservable.subscribe(function () { this.updateValue() }.bind(this));
            intervalAmountObservable.subscribe(function () { this.updateValue() }.bind(this));
            intervalTypeObservable.subscribe(function () { this.updateValue() }.bind(this));
            repetitionAmountObservable.subscribe(function () { this.updateValue() }.bind(this));
            repetitionTypeObservable.subscribe(function () { this.updateValue() }.bind(this));

            this.rows.push({
                identifier: identifier,
                title: titleObservable,
                interval_amount: intervalAmountObservable,
                interval_type: intervalTypeObservable,
                repetition_amount: repetitionAmountObservable,
                repetition_type: repetitionTypeObservable,
            });
        }
    });
})
