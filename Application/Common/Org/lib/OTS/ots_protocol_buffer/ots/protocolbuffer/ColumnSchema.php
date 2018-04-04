<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.ColumnSchema)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method string getName()
 * @method void setName(\string $value)
 * @method \ots\protocolbuffer\ColumnType getType()
 * @method void setType(\ots\protocolbuffer\ColumnType $value)
 */
class ColumnSchema extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.ColumnSchema)
  
  /**
   * @var string $name
   * @tag 1
   * @label required
   * @type \ProtocolBuffers::TYPE_STRING
   **/
  protected $name;
  
  /**
   * @var \ots\protocolbuffer\ColumnType $type
   * @tag 2
   * @label required
   * @type \ProtocolBuffers::TYPE_ENUM
   * @see \ots\protocolbuffer\ColumnType
   **/
  protected $type;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.ColumnSchema)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.ColumnSchema)

  /**
   * get descriptor for protocol buffers
   * 
   * @return \ProtocolBuffersDescriptor
   */
  public static function getDescriptor()
  {
    static $descriptor;
    
    if (!isset($descriptor)) {
      $desc = new \ProtocolBuffers\DescriptorBuilder();
      $desc->addField(1, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_STRING,
        "name"     => "name",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => "",
      )));
      $desc->addField(2, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_ENUM,
        "name"     => "type",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.ColumnSchema)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}