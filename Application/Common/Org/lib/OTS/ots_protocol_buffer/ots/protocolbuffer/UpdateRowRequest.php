<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.UpdateRowRequest)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method string getTableName()
 * @method void setTableName(\string $value)
 * @method \ots\protocolbuffer\Condition getCondition()
 * @method void setCondition(\ots\protocolbuffer\Condition $value)
 * @method array getPrimaryKey()
 * @method void appendPrimaryKey(\ots\protocolbuffer\Column $value)
 * @method array getAttributeColumns()
 * @method void appendAttributeColumns(\ots\protocolbuffer\ColumnUpdate $value)
 */
class UpdateRowRequest extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.UpdateRowRequest)
  
  /**
   * @var string $table_name
   * @tag 1
   * @label required
   * @type \ProtocolBuffers::TYPE_STRING
   **/
  protected $table_name;
  
  /**
   * @var \ots\protocolbuffer\Condition $condition
   * @tag 2
   * @label required
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $condition;
  
  /**
   * @var array $primary_key
   * @tag 3
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   * @see \ots\protocolbuffer\Column
   **/
  protected $primary_key;
  
  /**
   * @var array $attribute_columns
   * @tag 4
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   * @see \ots\protocolbuffer\ColumnUpdate
   **/
  protected $attribute_columns;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.UpdateRowRequest)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.UpdateRowRequest)

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
        "name"     => "table_name",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => "",
      )));
      $desc->addField(2, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "condition",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\Condition',
      )));
      $desc->addField(3, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "primary_key",
        "required" => false,
        "optional" => false,
        "repeated" => true,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\Column',
      )));
      $desc->addField(4, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "attribute_columns",
        "required" => false,
        "optional" => false,
        "repeated" => true,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\ColumnUpdate',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.UpdateRowRequest)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}
