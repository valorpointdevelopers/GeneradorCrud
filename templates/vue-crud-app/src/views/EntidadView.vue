<template>
  <div class="container">
    <h1>Gestión de {Entidad}</h1>
    
    <div class="form-section">
      <{Entidad}Form
        :{entidad}ToEdit="{entidad}ToEdit"
        @refresh="fetch{Entidad}"
        @resetEdit="{entidad}ToEdit = null"
      />
    </div>

    <div class="list-section">
      <{Entidad}List
        v-model:{entidad}="{entidad}"
        @edit="edit{Entidad}"
        @refresh="fetch{Entidad}"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import {Entidad}Form from '../components/{Entidad}Form.vue';
import {Entidad}List from '../components/{Entidad}List.vue';
{serviceImport} 

const {entidad} = ref([]);
const {entidad}ToEdit = ref(null);

{serviceCalls}

const edit{Entidad} = ({entidad}) => {
  {entidad}ToEdit.value = {entidad};
};

onMounted(fetch{Entidad});
</script>

<style scoped>
.container {
  max-width: 700px;
  margin: 2rem auto;
  padding: 1.5rem;
  border: 1px solid rgba(89, 93, 97, 0.57);
  border-radius: 10px;
  background-color:rgba(39, 10, 10, 0.01);
}

h1 {
  text-align: center;
  color: #ddd;
}

.form-section,
.list-section {
  margin-top: 2rem;
}

form input {
  margin: 0.5rem;
  padding: 0.5rem;
  width: calc(50% - 1rem);
  border: 1px solid #ccc;
  border-radius: 5px;
}

form button {
  margin: 0.5rem;
  padding: 0.5rem 1rem;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

form button[type="button"] {
  background-color: #6c757d;
}

ul {
  list-style: none;
  padding: 0;
}

li {
  margin: 0.5rem 0;
  background: #fff;
  padding: 0.8rem;
  border-radius: 5px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid #ccc;
}

li button {
  margin-left: 0.5rem;
  padding: 0.3rem 0.6rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

li button:first-of-type {
  background-color: #ffc107;
  color: #000;
}

li button:last-of-type {
  background-color: #dc3545;
  color: #fff;
}
</style>
