package com.javatemplates.javacrud.controllers;


import com.javatemplates.javacrud.services.UsuarioService;
import com.javatemplates.javacrud.entitys.Usuario;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;


@RestController
@RequestMapping("/api/usuarios")
public class UsuarioController {

    @Autowired
    private UsuarioService usuarioService;

    // Crear un nuevo usuario
    @PostMapping("/crearusuario")
    public ResponseEntity<Usuario> crearUsuario(@RequestBody Usuario usuario) {
        Usuario nuevoUsuario = usuarioService.crearUsuario(usuario);
        return ResponseEntity.ok(nuevoUsuario);
    }

    // Obtener todos los usuarios
    @GetMapping("/listar")
    public ResponseEntity<List<Usuario>> obtenerUsuarios() {
        List<Usuario> usuarios = usuarioService.obtenerUsuarios();
        return ResponseEntity.ok(usuarios);
    }

    // Obtener un usuario por ID
    //@GetMapping("/obtener/{id}")
    //public ResponseEntity<Usuario> obtener{ENTIDAD}PorId(@PathVariable Integer id) {
      //  return usuarioService.obtenerUsuarioPorId(id)
        //        .map(ResponseEntity::ok)
          //      .orElse(ResponseEntity.notFound().build());
   // }

    // Actualizar un usuario
    @PutMapping("/actualizar/{id}")
    public ResponseEntity<Usuario> actualizarUsuario(@PathVariable Integer id, @RequestBody Usuario usuario) {
        Usuario usuarioActualizado = usuarioService.actualizarUsuario(id, usuario);
        return ResponseEntity.ok(usuarioActualizado);
    }

    // Eliminar un usuario
    @DeleteMapping("/eliminar/{id}")
    public ResponseEntity<Void> eliminarUsuario(@PathVariable Integer id) {
        usuarioService.eliminarUsuario(id);
        return ResponseEntity.noContent().build();
    }
}
