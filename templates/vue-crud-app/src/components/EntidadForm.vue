<template>
  <div>
    <h2>{{ {entidad}ToEdit ? 'Editar {Entidad}' : 'Agregar {Entidad}' }}</h2>
    <form @submit.prevent="save{Entidad}">
      {columnsFormFields}

      <button type="submit">{{ {entidad}ToEdit ? 'Actualizar' : 'Agregar' }}</button>
      <button type="button" @click="resetForm">Cancelar</button>
    </form>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const emit = defineEmits(['refresh', 'resetEdit']);
const props = defineProps({
  {entidad}ToEdit: Object
});

const form = ref({
  {columnsFormState}
});

// Rellenar el formulario si cambia {entidad}ToEdit
watch(
  () => props.{entidad}ToEdit,
  (val) => {
    if (val) {
      form.value = { ...val };
    } else {
      for (const key in form.value) {
        form.value[key] = '';
      }
    }
  },
  { immediate: true }
);

const save{Entidad} = async () => {
  try {
    if (props.{entidad}ToEdit && props.{entidad}ToEdit.{primaryKey}) {
      // Actualizar
       await axios.put(`http://localhost:8080/api/{entidad}/actualizar{entidad}/${props.{entidad}ToEdit.{primaryKey}}`, form.value);

    } else {
      // Crear
      await axios.post(`http://localhost:8080/api/{entidad}/crear{entidad}`, form.value);
    }

    emit('refresh');
    emit('resetEdit');

    // Limpiar formulario
    form.value = Object.fromEntries(Object.keys(form.value).map(k => [k, '']));
  } catch (error) {
    console.error("Error al guardar {Entidad}:", error);
  }
};

const resetForm = () => {
  emit('resetEdit');
  form.value = Object.fromEntries(Object.keys(form.value).map(k => [k, '']));
};
</script>

<style scoped>
form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

input {
  padding: 0.8rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.3s;
  background-color: #fff;
  color: #000;
}

input:focus {
  border-color: #007bff;
  outline: none;
  background-color: rgba(130, 132, 133, 0.19);
  color: #f5f5f5;
}

input::placeholder {
  color: #888;
  font-style: italic;
  font-size: 0.95rem;
}

button {
  padding: 0.6rem 1rem;
  border-radius: 5px;
  border: none;
  cursor: pointer;
}

button[type="submit"] {
  background-color: #007bff;
  color: white;
}

button[type="button"] {
  background-color: #6c757d;
  color: white;
}
</style>
