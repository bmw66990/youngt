<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.TableInBatchGetRowRequest)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method string getTableName()
 * @method void setTableName(\string $value)
 * @method array getRows()
 * @method void appendRows(\ots\protocolbuffer\RowInBatchGetRowRequest $value)
 * @method array getColumnsToGet()
 * @method void appendColumnsToGet(\string $value)
 */
class TableInBatchGetRowRequest extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.TableInBatchGetRowRequest)
  
  /**
   * @var string $table_name
   * @tag 1
   * @label required
   * @type \ProtocolBuffers::TYPE_STRING
   **/
  protected $table_name;
  
  /**
   * @var array $rows
   * @tag 2
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   * @see \ots\protocolbuffer\RowInBatchGetRowRequest
   **/
  protected $rows;
  
  /**
   * @var array $columns_to_get
   * @tag 3
   * @label optional
   * @type \ProtocolBuffers::TYPE_STRING
   **/
  protected $columns_to_get;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.TableInBatchGetRowRequest)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.TableInBatchGetRowRequest)

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
        "name"     => "rows",
        "required" => false,
        "optional" => false,
        "repeated" => true,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\RowInBatchGetRowRequest',
      )));
      $desc->addField(3, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_STRING,
        "name"     => "columns_to_get",
        "required" => false,
        "optional" => false,
        "repeated" => true,
        "packable" => false,
        "default"  => "",
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.TableInBatchGetRowRequest)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}
