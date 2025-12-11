package com.javatemplates.javacrud.repositorys;

import com.javatemplates.javacrud.entitys.Usuario;
import org.springframework.data.jpa.repository.JpaRepository;

public interface UsuarioRepository extends JpaRepository<Usuario, Integer> {
}
