// Vue.js Components

// Base Component Mixin
const baseComponent = {
    methods: {
        showError(message) {
            this.$emit('error', message);
        },
        showSuccess(message) {
            this.$emit('success', message);
        },
        setLoading(isLoading) {
            this.$emit('loading', isLoading);
        }
    }
};

// Form Component
const FormComponent = {
    template: `
        <form @submit.prevent="handleSubmit" :class="{'loading': loading}">
            <slot></slot>
        </form>
    `,
    data() {
        return {
            loading: false
        };
    },
    methods: {
        handleSubmit() {
            if (this.validate()) {
                this.loading = true;
                this.$emit('submit');
            }
        },
        validate() {
            const inputs = this.$el.querySelectorAll('.form-control');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('has-error');
                    isValid = false;
                } else {
                    input.classList.remove('has-error');
                }
            });

            return isValid;
        }
    }
};

// Table Component
const TableComponent = {
    template: `
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th v-for="(header, index) in headers" :key="index">
                            {{ header }}
                        </th>
                        <th v-if="actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in items" :key="index">
                        <td v-for="(header, i) in headers" :key="i">
                            {{ item[header] }}
                        </td>
                        <td v-if="actions" class="actions">
                            <slot name="actions" :item="item"></slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    `,
    props: {
        headers: Array,
        items: Array,
        actions: Boolean
    }
};

// Modal Component
const ModalComponent = {
    template: `
        <div class="modal" :class="{'show': visible}" @click.self="close">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ title }}</h5>
                        <button type="button" class="close" @click="close">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <slot></slot>
                    </div>
                    <div class="modal-footer" v-if="showFooter">
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </div>
    `,
    props: {
        title: String,
        showFooter: {
            type: Boolean,
            default: true
        }
    },
    data() {
        return {
            visible: false
        };
    },
    methods: {
        open() {
            this.visible = true;
        },
        close() {
            this.visible = false;
        }
    }
};
