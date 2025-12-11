package com.{PACKAGE}.entities;

import jakarta.persistence.*;
//[[nombre, tipo],[edad, int]]
@Entity
@Table(name = "{entidad}")
public class {EntidadM}Entity {


    {COLUMNS}

    // Constructor vacío
    public {EntidadM}Entity() {
    }

    // Constructor con parámetros
    public {EntidadM}Entity({CONSTRUCTOR_PARAMS}) {
        {ASSIGNMENTS}
    }

    // Getters y Setters

    {GETTERS_SETTERS}
}


