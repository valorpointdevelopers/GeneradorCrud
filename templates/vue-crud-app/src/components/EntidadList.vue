<template>
  <div>
    <h2>Lista de {Entidad}</h2>
    <ul>
      <li v-for="u in {entidad}" :key="u.{primaryKey}">
        {columnsListData}
        <button @click="$emit('edit', u)">Editar</button>
        <button @click="delete{Entidad}(u.{primaryKey})">Eliminar</button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { defineProps, onMounted } from 'vue';
import axios from 'axios';
{serviceImport}
const emit = defineEmits(['edit']);
const {entidad} = defineModel('{entidad}', { type: Array });

{serviceCalls}


const delete{Entidad} = async ({primaryKey}) => {
  const confirmed = confirm('¿Estás seguro de que deseas eliminar esto?');
  if (!confirmed) return;

   await {entidad}Service.delete({primaryKey});  // usar el service
  fetch{Entidad}(); 
};

onMounted(fetch{Entidad});
defineExpose({ fetch{Entidad} });
</script>

