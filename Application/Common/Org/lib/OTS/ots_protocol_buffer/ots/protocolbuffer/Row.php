<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.Row)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method array getPrimaryKeyColumns()
 * @method void appendPrimaryKeyColumns(\ots\protocolbuffer\Column $value)
 * @method array getAttributeColumns()
 * @method void appendAttributeColumns(\ots\protocolbuffer\Column $value)
 */
class Row extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.Row)
  
  /**
   * @var array $primary_key_columns
   * @tag 1
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   * @see \ots\protocolbuffer\Column
   **/
  protected $primary_key_columns;
  
  /**
   * @var array $attribute_columns
   * @tag 2
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   * @see \ots\protocolbuffer\Column
   **/
  protected $attribute_columns;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.Row)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.Row)

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
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "primary_key_columns",
        "required" => false,
        "optional" => false,
        "repeated" => true,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\Column',
      )));
      $desc->addField(2, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "attribute_columns",
        "required" => false,
        "optional" => false,
        "repeated" => true,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\Column',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.Row)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}
